<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Network;
use Dashtainer\Entity;
use Dashtainer\Repository;
use Dashtainer\Tests\Mock;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NetworkTest extends KernelTestCase
{
    /** @var Network */
    protected $network;

    /** @var MockObject|Repository\Docker\Network */
    protected $networkRepo;

    protected function setUp()
    {
        /** @var $em MockObject|ORM\EntityManagerInterface */
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->networkRepo = new Mock\RepoDockerNetwork($em);

        $wordListFile = __DIR__ . '/../../files/networkNames.txt';

        $this->network = new Network($this->networkRepo, $wordListFile);
    }

    public function testDeleteRemovesFromServices()
    {
        $networkA = new Entity\Docker\Network();
        $networkA->setName('networkA');

        $networkB = new Entity\Docker\Network();
        $networkB->setName('networkB');

        $serviceA = new Entity\Docker\Service();
        $serviceA->setName('serviceA')
            ->addNetwork($networkA)
            ->addNetwork($networkB);

        $serviceB = new Entity\Docker\Service();
        $serviceB->setName('serviceB')
            ->addNetwork($networkA)
            ->addNetwork($networkB);

        $serviceC = new Entity\Docker\Service();
        $serviceC->setName('serviceC')
            ->addNetwork($networkB);

        $networkA->addService($serviceA)
            ->addService($serviceB);

        $networkB->addService($serviceA)
            ->addService($serviceB)
            ->addService($serviceC);

        $this->network->delete($networkA);

        $this->assertNotContains($networkA, $serviceA->getNetworks());
        $this->assertNotContains($networkA, $serviceB->getNetworks());

        $this->assertNotContains($serviceA, $networkA->getServices());
        $this->assertNotContains($serviceB, $networkA->getServices());

        $this->assertContains($networkB, $serviceA->getNetworks());
        $this->assertContains($networkB, $serviceB->getNetworks());
        $this->assertContains($networkB, $serviceC->getNetworks());
    }

    public function testGenerateNameReturnsUnusedNames()
    {
        $project = new Entity\Docker\Project();

        $networkA = new Entity\Docker\Network();
        $networkA->setName('networkA')
            ->setProject($project);

        $networkB = new Entity\Docker\Network();
        $networkB->setName('networkB')
            ->setProject($project);

        $networkC = new Entity\Docker\Network();
        $networkC->setName('networkC')
            ->setProject($project);

        $networkD = new Entity\Docker\Network();
        $networkD->setName('networkD')
            ->setProject($project);

        $networks = [
            $networkA,
            $networkB,
            $networkC,
            $networkD,
        ];

        $project->addNetwork($networkA)
            ->addNetwork($networkB)
            ->addNetwork($networkC)
            ->addNetwork($networkD);

        $result = $this->network->generateName($project);

        $availableNames = [
            'unusedNetworkNameA',
            'unusedNetworkNameB',
            'unusedNetworkNameC',
        ];

        $this->assertContains($result, $availableNames);
    }
}
