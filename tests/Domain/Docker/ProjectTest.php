<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Project;
use Dashtainer\Entity\User;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Mock;

class ProjectTest extends DomainAbstract
{
    /** @var Project */
    protected $project;

    protected function setUp()
    {
        $this->project = new Project(new Mock\RepoDockerProject($this->getEm()));
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
        $project = $this->createProject('Project Name');

        $networkA = $this->createNetwork('network-a');
        $project->addNetwork($networkA);

        $networkB = $this->createNetwork('network-b');
        $project->addNetwork($networkB);

        $projectSecretA = $this->createProjectSecret('secret-a');
        $project->addSecret($projectSecretA);

        $serviceSecretA = $this->createServiceSecret('service-secret-a');
        $projectSecretA->addServiceSecret($serviceSecretA);

        $projectSecretB = $this->createProjectSecret('secret-b');
        $project->addSecret($projectSecretB);

        $serviceSecretB = $this->createServiceSecret('service-secret-b');
        $projectSecretB->addServiceSecret($serviceSecretB);

        $volumeA = $this->createProjectVolume('volume-a');
        $project->addVolume($volumeA);

        $volumeB = $this->createProjectVolume('volume-b');
        $project->addVolume($volumeB);

        // Service A

        $serviceA = $this->createService('service-a');
        $project->addService($serviceA);

        $serviceAMetaA = $this->createServiceMeta('service-a-meta-a');
        $serviceA->addMeta($serviceAMetaA);

        $serviceAMetaB = $this->createServiceMeta('service-a-meta-b');
        $serviceA->addMeta($serviceAMetaB);

        $serviceAPortA = $this->createPort('port-a', 8080, 8081);
        $serviceA->addPort($serviceAPortA);

        $serviceAVolumeA = $this->createServiceVolume('service-a-volume-a');
        $serviceA->addVolume($serviceAVolumeA);
        $volumeA->addServiceVolume($serviceAVolumeA);

        $serviceAVolumeB = $this->createServiceVolume('service-a-volume-b');
        $serviceA->addVolume($serviceAVolumeB);
        $volumeA->addServiceVolume($serviceAVolumeB);

        $networkA->addService($serviceA);

        $projectSecretA->setOwner($serviceA);
        $serviceA->addSecret($serviceSecretA);

        // Service B

        $serviceB = $this->createService('service-b');
        $project->addService($serviceB);

        $serviceA->addChild($serviceB);

        $serviceBMetaA = $this->createServiceMeta('service-b-meta-a');
        $serviceB->addMeta($serviceBMetaA);

        $serviceBMetaB = $this->createServiceMeta('service-b-meta-b');
        $serviceB->addMeta($serviceBMetaB);

        $serviceBPortA = $this->createPort('port-b', 8080, 8081);
        $serviceB->addPort($serviceBPortA);

        $serviceBVolumeA = $this->createServiceVolume('service-b-volume-a');
        $serviceB->addVolume($serviceBVolumeA);

        $serviceBVolumeB = $this->createServiceVolume('service-b-volume-b');
        $serviceB->addVolume($serviceBVolumeB);

        $networkB->addService($serviceB);

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
        $this->assertEmpty($serviceA->getPorts());
        $this->assertEmpty($serviceA->getSecrets());

        $this->assertEmpty($serviceB->getVolumes());
        $this->assertEmpty($serviceB->getMetas());
        $this->assertEmpty($serviceB->getPorts());
        $this->assertEmpty($serviceB->getSecrets());
    }
}
