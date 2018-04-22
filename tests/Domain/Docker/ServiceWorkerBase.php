<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form\Docker\Service\CreateAbstract;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceWorkerBase extends KernelTestCase
{
    /** @var MockObject|ORM\EntityManagerInterface */
    protected $em;

    /** @var CreateAbstract */
    protected $form;

    /** @var MockObject|Repository\Docker\Network */
    protected $networkRepo;

    /** @var Entity\Docker\Project */
    protected $project;

    /** @var Entity\Docker\Network */
    protected $publicNetwork;

    protected $seededPrivateNetworks = [];

    /** @var MockObject|Repository\Docker\Service */
    protected $serviceRepo;

    /** @var Entity\Docker\ServiceType */
    protected $serviceType;

    /** @var MockObject|Repository\Docker\ServiceType */
    protected $serviceTypeRepo;

    protected function setUp()
    {
        parent::setUp();

        $this->em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->networkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $this->serviceRepo = $this->getMockBuilder(Repository\Docker\Service::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $this->serviceTypeRepo = $this->getMockBuilder(Repository\Docker\ServiceType::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $this->publicNetwork = new Entity\Docker\Network();
        $this->publicNetwork->setName('public-network')
            ->setIsEditable(false)
            ->setIsPublic(true);

        $this->networkRepo->expects($this->any())
            ->method('getPublicNetwork')
            ->will($this->returnValue($this->publicNetwork));

        $this->project = new Entity\Docker\Project();
        $this->project->setName('project-name');

        $this->project->addNetwork($this->publicNetwork);

        $this->serviceType = new Entity\Docker\ServiceType();
        $this->serviceType->setName('service-type-name');
    }

    protected function seedProjectWithPrivateNetworks()
    {
        $privateNetworkA = new Entity\Docker\Network();
        $privateNetworkA->setName('private-network-a');

        $privateNetworkB = new Entity\Docker\Network();
        $privateNetworkB->setName('private-network-b');

        $privateNetworkC = new Entity\Docker\Network();
        $privateNetworkC->setName('private-network-c');

        $this->project->addNetwork($privateNetworkA)
            ->addNetwork($privateNetworkB)
            ->addNetwork($privateNetworkC);

        $this->seededPrivateNetworks = [
            'private-network-a' => $privateNetworkA,
            'private-network-b' => $privateNetworkB,
            'private-network-c' => $privateNetworkC,
        ];
    }

    protected function networkRepoDefaultExpects()
    {
        $this->seedProjectWithPrivateNetworks();

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));
    }

    /**
     * @return MockObject|Repository\Docker\Network
     */
    protected function getUpdateNetworkRepo() : MockObject
    {
        $networkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $networkRepo->expects($this->any())
            ->method('getPublicNetwork')
            ->will($this->returnValue($this->publicNetwork));

        $networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([
                $this->seededPrivateNetworks['private-network-a'],
                $this->seededPrivateNetworks['private-network-b'],
            ]));

        return $networkRepo;
    }
}
