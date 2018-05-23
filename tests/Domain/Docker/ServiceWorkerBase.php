<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form\Docker\Service\CreateAbstract;
use Dashtainer\Repository;
use Dashtainer\Tests\Mock;

use Doctrine\Common\Persistence\ObjectRepository;
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

    /** @var Domain\Docker\Secret */
    protected $secretDomain;

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

        $repository = $this->getMockBuilder(ObjectRepository::class)
            ->getMock();

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->secretDomain = new Domain\Docker\Secret(
            new Mock\RepoDockerSecret($this->em)
        );

        $this->serviceRepo = new Mock\RepoDockerService($this->em);

        $this->serviceTypeRepo = new Mock\RepoDockerServiceType($this->em);

        $this->project = new Entity\Docker\Project();
        $this->project->setName('project-name');

        $this->setupNetwork();
        $this->setupOtherServiceSecrets();

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

    protected function setupOtherServiceSecrets()
    {
        $otherServiceTypeCategory = new Entity\Docker\ServiceCategory();
        $otherServiceTypeCategory->setName('other service type category')
            ->setOrder(1);

        $otherServiceType = new Entity\Docker\ServiceType();
        $otherServiceType->setName('other service type')
            ->setCategory($otherServiceTypeCategory);

        $otherServiceTypeCategory->addType($otherServiceType);

        $otherService1 = new Entity\Docker\Service();
        $otherService1->setName('other service 1')
            ->setProject($this->project)
            ->setType($otherServiceType);

        $otherServiceType->addService($otherService1);

        $otherProjectSecret1 = new Entity\Docker\Secret();
        $otherProjectSecret1 ->fromArray(['id' => 'other-project-secret-1-id']);
        $otherProjectSecret1->setName('other project secret 1')
            ->setContents('other project secret 1 contents')
            ->setOwner($otherService1)
            ->setProject($this->project);

        $otherServiceSecret1 = new Entity\Docker\ServiceSecret();
        $otherServiceSecret1->setTarget('other service secret 1 target')
            ->setService($otherService1)
            ->setProjectSecret($otherProjectSecret1);

        $otherService1->addSecret($otherServiceSecret1);

        $otherService2 = new Entity\Docker\Service();
        $otherService2->setName('other service 2')
            ->setProject($this->project)
            ->setType($otherServiceType);

        $otherServiceType->addService($otherService2);

        $otherProjectSecret2 = new Entity\Docker\Secret();
        $otherProjectSecret2 ->fromArray(['id' => 'other-project-secret-2-id']);
        $otherProjectSecret2->setName('other project secret 2')
            ->setContents('other project secret 2 contents')
            ->setOwner($otherService2)
            ->setProject($this->project);

        $otherServiceSecret2 = new Entity\Docker\ServiceSecret();
        $otherServiceSecret2->setTarget('other service secret 2 target')
            ->setService($otherService2)
            ->setProjectSecret($otherProjectSecret2);

        $otherService2->addSecret($otherServiceSecret2);

        $this->project->addService($otherService1)
            ->addService($otherService2)
            ->addSecret($otherProjectSecret1)
            ->addSecret($otherProjectSecret2);
    }
}
