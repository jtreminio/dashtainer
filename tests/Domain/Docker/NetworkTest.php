<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Network;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NetworkTest extends KernelTestCase
{
    /** @var Network */
    protected $network;

    /** @var MockObject|Repository\Docker\Network */
    protected $networkRepo;

    /** @var MockObject|Repository\Docker\Service */
    protected $serviceRepo;

    protected function setUp()
    {
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->networkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->serviceRepo = $this->getMockBuilder(Repository\Docker\Service::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $wordListFile = __DIR__ . '/../../files/networkNames.txt';

        $this->network = new Network($this->networkRepo, $this->serviceRepo, $wordListFile);
    }

    public function testCreateFromFormGeneratesEntity()
    {
        $project = new Entity\Docker\Project();
        $serviceNames = ['serviceA', 'serviceB'];

        $serviceA = new Entity\Docker\Service();
        $serviceB = new Entity\Docker\Service();

        /** @var Entity\Docker\Service[] $services */
        $services = [$serviceA, $serviceB];

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with(['project' => $project, 'name' => $serviceNames])
            ->will($this->returnValue($services));

        $this->networkRepo->expects($this->once())
            ->method('save');

        $form = new Form\Docker\NetworkCreateUpdate();
        $form->project  = $project;
        $form->name     = 'networkName';
        $form->services = $serviceNames;

        $network = $this->network->createFromForm($form);

        foreach ($network->getServices() as $service) {
            $this->assertTrue(in_array($service, $services));
        }

        foreach ($services as $service) {
            $this->assertTrue(in_array($network, $service->getNetworks()->toArray()));
        }
    }

    public function testUpdateFromFormAddsAndRemovesServices()
    {
        $network = new Entity\Docker\Network();
        $network->setName('network');

        $serviceA = new Entity\Docker\Service();
        $serviceA->setName('serviceA')
            ->addNetwork($network);

        $serviceB = new Entity\Docker\Service();
        $serviceB->setName('serviceB')
            ->addNetwork($network);

        $serviceC = new Entity\Docker\Service();
        $serviceC->setName('serviceC');

        $serviceD = new Entity\Docker\Service();
        $serviceD->setName('serviceD');

        $serviceE = new Entity\Docker\Service();
        $serviceE->setName('serviceE');

        $services = [
            $serviceA,
            $serviceB,
            $serviceC,
            $serviceD,
            $serviceE,
        ];

        $network->addService($serviceA)
            ->addService($serviceB);

        $form = new Form\Docker\NetworkCreateUpdate();
        $form->project  = new Entity\Docker\Project();
        $form->name     = 'networkName';
        $form->services = [
            $serviceA->getName(),
            $serviceC->getName(),
        ];

        $this->networkRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $form->name])
            ->will($this->returnValue($network));

        $this->serviceRepo->expects($this->once())
            ->method('findAllByProject')
            ->with($form->project)
            ->will($this->returnValue($services));

        $this->networkRepo->expects($this->once())
            ->method('save')
            ->with($network, ...[$serviceB, $serviceD, $serviceE], ...[$serviceA, $serviceC]);

        $this->network->updateFromForm($form);
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

        $this->networkRepo->expects($this->once())
            ->method('findBy')
            ->with(['project' => $project])
            ->will($this->returnValue($networks));

        $result = $this->network->generateName($project);

        $availableNames = [
            'unusedNetworkNameA',
            'unusedNetworkNameB',
            'unusedNetworkNameC',
        ];

        $this->assertTrue(in_array($result, $availableNames));
    }
}
