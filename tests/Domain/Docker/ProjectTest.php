<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Project;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Entity\User;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Mock;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjectTest extends KernelTestCase
{
    /** @var Project */
    protected $project;

    protected function setUp()
    {
        /** @var $em MockObject|ORM\EntityManagerInterface */
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->project = new Project(new Mock\RepoDockerProject($em));
    }

    public function testCreateFromFormCreatesProject()
    {
        $form = new Form\ProjectCreateUpdate();
        $form->name = 'Project Name';
        $form->user = new User();

        $project = $this->project->create($form);

        $networks = [];
        foreach ($project->getNetworks() as $network) {
            $networks [$network->getName()]= $network;
        }

        $this->assertNotNull($networks['public']);
        $this->assertNotNull($networks['private']);

        $this->assertSame($form->user, $project->getUser());
    }

    public function testDeleteTraversesChildren()
    {
        $project = new Entity\Project();
        $project->setName('Project Name');

        $networkA = new Entity\Network();
        $networkA->setName('network-a')
            ->setProject($project);
        $project->addNetwork($networkA);

        $networkB = new Entity\Network();
        $networkB->setName('network-b')
            ->setProject($project);
        $project->addNetwork($networkB);

        $projectSecretA = new Entity\Secret();
        $projectSecretA->setName('secret-a')
            ->setProject($project);
        $project->addSecret($projectSecretA);

        $serviceSecretA = new Entity\ServiceSecret();
        $serviceSecretA->setProjectSecret($projectSecretA);

        $projectSecretB = new Entity\Secret();
        $projectSecretB->setName('secret-b')
            ->setProject($project);
        $project->addSecret($projectSecretB);

        $serviceSecretB = new Entity\ServiceSecret();
        $serviceSecretB->setProjectSecret($projectSecretB);

        $volumeA = new Entity\Volume();
        $volumeA->setName('volume-a')
            ->setProject($project);
        $project->addVolume($volumeA);

        $volumeB = new Entity\Volume();
        $volumeB->setName('volume-b')
            ->setProject($project);
        $project->addVolume($volumeB);

        // Service A

        $serviceA = new Entity\Service();
        $serviceA->setName('servica-a')
            ->setProject($project);
        $project->addService($serviceA);

        $serviceAMetaA = new Entity\ServiceMeta();
        $serviceAMetaA->setName('service-a-meta-a')
            ->setService($serviceA);
        $serviceA->addMeta($serviceAMetaA);

        $serviceAMetaB = new Entity\ServiceMeta();
        $serviceAMetaB->setName('service-a-meta-b')
            ->setService($serviceA);
        $serviceA->addMeta($serviceAMetaB);

        $serviceAVolumeA = new Entity\ServiceVolume();
        $serviceAVolumeA->setName('service-a-volume-a')
            ->setService($serviceA)
            ->setProjectVolume($volumeA);
        $serviceA->addVolume($serviceAVolumeA);
        $volumeA->addServiceVolume($serviceAVolumeA);

        $serviceAVolumeB = new Entity\ServiceVolume();
        $serviceAVolumeB->setName('service-a-volume-b')
            ->setService($serviceA)
            ->setProjectVolume($volumeA);
        $serviceA->addVolume($serviceAVolumeB);
        $volumeA->addServiceVolume($serviceAVolumeB);

        $networkA->addService($serviceA);
        $serviceA->addNetwork($networkA);

        $serviceSecretA->setService($serviceA)
            ->setProjectSecret($projectSecretA);
        $projectSecretA->setOwner($serviceA);
        $serviceA->addSecret($serviceSecretA);

        // Service B

        $serviceB = new Entity\Service();
        $serviceB->setName('servica-b')
            ->setProject($project);
        $project->addService($serviceB);

        $serviceB->setParent($serviceA);
        $serviceA->addChild($serviceB);

        $serviceBMetaA = new Entity\ServiceMeta();
        $serviceBMetaA->setName('service-b-meta-a')
            ->setService($serviceB);
        $serviceB->addMeta($serviceBMetaA);

        $serviceBMetaB = new Entity\ServiceMeta();
        $serviceBMetaB->setName('service-b-meta-b')
            ->setService($serviceB);
        $serviceB->addMeta($serviceBMetaB);

        $serviceBVolumeA = new Entity\ServiceVolume();
        $serviceBVolumeA->setName('service-b-volume-a')
            ->setService($serviceB);
        $serviceB->addVolume($serviceBVolumeA);

        $serviceBVolumeB = new Entity\ServiceVolume();
        $serviceBVolumeB->setName('service-b-volume-b')
            ->setService($serviceB);
        $serviceB->addVolume($serviceBVolumeB);

        $networkB->addService($serviceB);
        $serviceB->addNetwork($networkB);

        $serviceSecretB->setService($serviceB)
            ->setProjectSecret($projectSecretB);
        $projectSecretB->setOwner($serviceB);
        $serviceB->addSecret($serviceSecretB);

        $this->project->delete($project);

        $this->assertEmpty($project->getNetworks());
        $this->assertEmpty($project->getSecrets());
        $this->assertEmpty($project->getVolumes());
        $this->assertEmpty($project->getServices());

        $this->assertEmpty($networkA->getServices());
        $this->assertEmpty($networkB->getServices());

        $this->assertEmpty($projectSecretA->getServiceSecrets());
        $this->assertEmpty($projectSecretB->getServiceSecrets());

        $this->assertEmpty($volumeA->getServiceVolumes());
        $this->assertEmpty($volumeB->getServiceVolumes());

        $this->assertEmpty($serviceA->getVolumes());
        $this->assertEmpty($serviceA->getMetas());
        $this->assertEmpty($serviceA->getSecrets());

        $this->assertEmpty($serviceB->getVolumes());
        $this->assertEmpty($serviceB->getMetas());
        $this->assertEmpty($serviceB->getSecrets());
    }
}
