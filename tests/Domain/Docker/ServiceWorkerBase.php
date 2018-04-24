<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form\Docker\Service\CreateAbstract;
use Dashtainer\Repository;
use Dashtainer\Tests\Mock;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceWorkerBase extends KernelTestCase
{
    /** @var MockObject|ORM\EntityManagerInterface */
    protected $em;

    /** @var CreateAbstract */
    protected $form;

    /** @var Mock\RepoDockerNetwork */
    protected $networkRepo;

    /** @var Entity\Docker\Project */
    protected $project;

    /** @var Entity\Docker\Network */
    protected $publicNetwork;

    // todo: delete
    protected $seededPrivateNetworks = [];

    /** @var MockObject|Repository\Docker\Service */
    protected $serviceRepo;

    /** @var Entity\Docker\ServiceType */
    protected $serviceType;

    /** @var Mock\RepoDockerServiceType */
    protected $serviceTypeRepo;

    protected function setUp()
    {
        parent::setUp();

        $this->em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->serviceRepo = new Mock\RepoDockerService($this->em);

        $this->serviceTypeRepo = new Mock\RepoDockerServiceType($this->em);

        $this->project = new Entity\Docker\Project();
        $this->project->setName('project-name');

        $this->setupNetwork();

        $this->serviceType = new Entity\Docker\ServiceType();
        $this->serviceType->setName('service-type-name');
    }

    protected function setupNetwork()
    {
        $this->networkRepo = new Mock\RepoDockerNetwork($this->em);

        $this->publicNetwork = new Entity\Docker\Network();
        $this->publicNetwork->setName('public-network')
            ->setIsEditable(false)
            ->setIsPublic(true);

        $privateNetworkA = new Entity\Docker\Network();
        $privateNetworkA->setName('private-network-a');

        $privateNetworkB = new Entity\Docker\Network();
        $privateNetworkB->setName('private-network-b');

        $privateNetworkC = new Entity\Docker\Network();
        $privateNetworkC->setName('private-network-c');

        $this->project->addNetwork($this->publicNetwork)
            ->addNetwork($privateNetworkA)
            ->addNetwork($privateNetworkB)
            ->addNetwork($privateNetworkC);

        // todo delete
        $this->seededPrivateNetworks = [
            'private-network-a' => $privateNetworkA,
            'private-network-b' => $privateNetworkB,
            'private-network-c' => $privateNetworkC,
        ];
    }
}
