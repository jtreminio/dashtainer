<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Project;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjectTest extends KernelTestCase
{
    /** @var Project */
    protected $project;

    /** @var MockObject|Repository\Docker\Project */
    protected $projectRepo;

    protected function setUp()
    {
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->projectRepo = $this->getMockBuilder(Repository\Docker\Project::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->project = new Project($this->projectRepo);
    }

    public function testCreateFromFormGeneratesEntity()
    {
        $user = new Entity\User();

        $form = new Form\Docker\ProjectCreateUpdate();
        $form->name = 'Project Name';

        $this->projectRepo->expects($this->exactly(2))
            ->method('save');

        $project = $this->project->createProjectFromForm($form, $user);

        $this->assertCount(1, $project->getNetworks());

        /** @var Entity\Docker\Network $publicNetwork */
        $publicNetwork = $project->getNetworks()->first();

        $this->assertEquals('projectname-public', $publicNetwork->getName());
    }

    public function testDeleteTraversesChildren()
    {
        $project = new Entity\Docker\Project();
        $project->setName('Project Name');

        $networkA = new Entity\Docker\Network();
        $networkA->setName('network-a')
            ->setProject($project);
        $project->addNetwork($networkA);

        $networkB = new Entity\Docker\Network();
        $networkB->setName('network-b')
            ->setProject($project);
        $project->addNetwork($networkB);

        $secretA = new Entity\Docker\Secret();
        $secretA->setName('secret-a')
            ->setProject($project);
        $project->addSecret($secretA);

        $secretB = new Entity\Docker\Secret();
        $secretB->setName('secret-b')
            ->setProject($project);
        $project->addSecret($secretB);

        $volumeA = new Entity\Docker\Volume();
        $volumeA->setName('volume-a')
            ->setProject($project);
        $project->addVolume($volumeA);

        $volumeB = new Entity\Docker\Volume();
        $volumeB->setName('volume-b')
            ->setProject($project);
        $project->addVolume($volumeB);

        // Service A

        $serviceA = new Entity\Docker\Service();
        $serviceA->setName('servica-a')
            ->setProject($project);
        $project->addService($serviceA);

        $serviceAMetaA = new Entity\Docker\ServiceMeta();
        $serviceAMetaA->setName('service-a-meta-a')
            ->setService($serviceA);
        $serviceA->addMeta($serviceAMetaA);

        $serviceAMetaB = new Entity\Docker\ServiceMeta();
        $serviceAMetaB->setName('service-a-meta-b')
            ->setService($serviceA);
        $serviceA->addMeta($serviceAMetaB);

        $serviceAVolumeA = new Entity\Docker\ServiceVolume();
        $serviceAVolumeA->setName('service-a-volume-a')
            ->setService($serviceA)
            ->setProjectVolume($volumeA);
        $serviceA->addVolume($serviceAVolumeA);
        $volumeA->addServiceVolume($serviceAVolumeA);

        $serviceAVolumeB = new Entity\Docker\ServiceVolume();
        $serviceAVolumeB->setName('service-a-volume-b')
            ->setService($serviceA)
            ->setProjectVolume($volumeA);
        $serviceA->addVolume($serviceAVolumeB);
        $volumeA->addServiceVolume($serviceAVolumeB);

        $networkA->addService($serviceA);
        $serviceA->addNetwork($networkA);

        $secretA->addService($serviceA);
        $serviceA->addSecret($secretA);

        // Service B

        $serviceB = new Entity\Docker\Service();
        $serviceB->setName('servica-b')
            ->setProject($project);
        $project->addService($serviceB);

        $serviceB->setParent($serviceA);
        $serviceA->addChild($serviceB);

        $serviceBMetaA = new Entity\Docker\ServiceMeta();
        $serviceBMetaA->setName('service-b-meta-a')
            ->setService($serviceB);
        $serviceB->addMeta($serviceBMetaA);

        $serviceBMetaB = new Entity\Docker\ServiceMeta();
        $serviceBMetaB->setName('service-b-meta-b')
            ->setService($serviceB);
        $serviceB->addMeta($serviceBMetaB);

        $serviceBVolumeA = new Entity\Docker\ServiceVolume();
        $serviceBVolumeA->setName('service-b-volume-a')
            ->setService($serviceB);
        $serviceB->addVolume($serviceBVolumeA);

        $serviceBVolumeB = new Entity\Docker\ServiceVolume();
        $serviceBVolumeB->setName('service-b-volume-b')
            ->setService($serviceB);
        $serviceB->addVolume($serviceBVolumeB);

        $networkB->addService($serviceB);
        $serviceB->addNetwork($networkB);

        $secretB->addService($serviceB);
        $serviceB->addSecret($secretB);

        $this->project->delete($project);

        $this->assertEmpty($project->getNetworks());
        $this->assertEmpty($project->getSecrets());
        $this->assertEmpty($project->getVolumes());
        $this->assertEmpty($project->getServices());

        $this->assertEmpty($networkA->getServices());
        $this->assertEmpty($networkB->getServices());

        $this->assertEmpty($secretA->getServices());
        $this->assertEmpty($secretB->getServices());

        $this->assertEmpty($volumeA->getServiceVolumes());
        $this->assertEmpty($volumeB->getServiceVolumes());

        $this->assertEmpty($serviceA->getVolumes());
        $this->assertEmpty($serviceA->getMetas());

        $this->assertEmpty($serviceB->getVolumes());
        $this->assertEmpty($serviceB->getMetas());
    }
}
