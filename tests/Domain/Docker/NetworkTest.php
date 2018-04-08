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
            $this->assertContains($service, $services);
        }

        foreach ($services as $service) {
            $this->assertContains($network, $service->getNetworks());
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

        $this->assertContains($serviceA, $network->getServices());
        $this->assertContains($serviceC, $network->getServices());

        $this->assertContains($network, $serviceA->getNetworks());
        $this->assertContains($network, $serviceC->getNetworks());

        $this->assertNotContains($serviceB, $network->getServices());
        $this->assertNotContains($serviceD, $network->getServices());
        $this->assertNotContains($serviceE, $network->getServices());

        $this->assertNotContains($network, $serviceB->getNetworks());
        $this->assertNotContains($network, $serviceD->getNetworks());
        $this->assertNotContains($network, $serviceE->getNetworks());
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

        $this->networkRepo->expects($this->once())
            ->method('save')
            ->with($networkA, $serviceA, $serviceB);

        $this->networkRepo->expects($this->once())
            ->method('delete')
            ->with($networkA);

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

        $this->assertContains($result, $availableNames);
    }
}
