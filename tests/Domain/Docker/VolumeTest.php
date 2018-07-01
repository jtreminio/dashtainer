<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Volume;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Tests\Mock;

class VolumeTest extends DomainAbstract
{
    /** @var Volume */
    protected $volume;

    protected function setUp()
    {
        $this->volume = new Volume(new Mock\RepoDockerVolume($this->getEm()));
    }

    public function testDeleteAllForService()
    {
        $projectVolumeA = $this->createProjectVolume('project-volume-a');
        $projectVolumeB = $this->createProjectVolume('project-volume-b');
        $projectVolumeC = $this->createProjectVolume('project-volume-c');

        $serviceVolumeA = $this->createServiceVolume('service-volume-a');
        $serviceVolumeB = $this->createServiceVolume('service-volume-b');
        $serviceVolumeC = $this->createServiceVolume('service-volume-c');
        $serviceVolumeD = $this->createServiceVolume('service-volume-d');
        $serviceVolumeE = $this->createServiceVolume('service-volume-e');
        $serviceVolumeF = $this->createServiceVolume('service-volume-f');
        $serviceVolumeG = $this->createServiceVolume('service-volume-g');

        $serviceA = $this->createService('service-a');
        $serviceB = $this->createService('service-b');

        $project = $this->createProject('project');
        $project->addService($serviceA)
            ->addService($serviceB)
            ->addVolume($projectVolumeA)
            ->addVolume($projectVolumeB)
            ->addVolume($projectVolumeC);

        /*
         * Owned, granted to other Services
         */
        $serviceA->addVolume($serviceVolumeA);
        $serviceB->addVolume($serviceVolumeB);
        $projectVolumeA->addServiceVolume($serviceVolumeA)
            ->addServiceVolume($serviceVolumeB)
            ->setOwner($serviceA);

        /*
         * Owned, not granted to other Services
         */
        $serviceA->addVolume($serviceVolumeC);
        $projectVolumeB->addServiceVolume($serviceVolumeC)
            ->setOwner($serviceA);

        /*
         * Not owned, granted
         */
        $serviceB->addVolume($serviceVolumeD);
        $serviceA->addVolume($serviceVolumeE);
        $projectVolumeC->addServiceVolume($serviceVolumeD)
            ->addServiceVolume($serviceVolumeE)
            ->setOwner($serviceB);

        /*
         * No ProjectVolume, only ServiceVolume
         */
        $serviceA->addVolume($serviceVolumeF);

        /*
         * Not owned by or granted to Service
         */
        $serviceB->addVolume($serviceVolumeG);

        $this->volume->deleteAllForService($serviceA);

        $this->assertEmpty($serviceA->getVolumes());
        $this->assertFalse($serviceB->getVolumes()->contains($serviceVolumeA));

        $this->assertNull($serviceVolumeA->getService());
        $this->assertNull($serviceVolumeA->getProjectVolume());
        $this->assertNull($serviceVolumeB->getService());
        $this->assertNull($serviceVolumeB->getProjectVolume());
        $this->assertNull($serviceVolumeC->getService());
        $this->assertNull($serviceVolumeC->getProjectVolume());

        $this->assertTrue($serviceB->getVolumes()->contains($serviceVolumeD));
        $this->assertSame($projectVolumeC, $serviceVolumeD->getProjectVolume());
    }

    public function testGetForNewServiceReturnsVolumes()
    {
        $grantableProjectVolumeA    = $this->createProjectVolume('project-volume-a');
        $notGrantableProjectVolumeB = $this->createProjectVolume('project-volume-b');

        $grantableServiceVolumeA    = $this->createServiceVolume('service-volume-a');
        $notGrantableServiceVolumeB = $this->createServiceVolume('service-volume-b');
        $notGrantableServiceVolumeC = $this->createServiceVolume('service-volume-c');

        $serviceA = $this->createService('service-a');

        $project = $this->createProject('project');
        $project->addService($serviceA)
            ->addVolume($grantableProjectVolumeA);

        // Grantable: ServiceVolume with ProjectVolume, owned by Service
        $serviceA->addVolume($grantableServiceVolumeA);
        $grantableProjectVolumeA->addServiceVolume($grantableServiceVolumeA)
            ->setOwner($serviceA);

        // Not Grantable: ServiceVolume with ProjectVolume, not owned by Service
        $serviceA->addVolume($notGrantableServiceVolumeB);
        $notGrantableProjectVolumeB->addServiceVolume($notGrantableServiceVolumeB);

        // Not Grantable: ServiceVolume without ProjectVolume
        $serviceA->addVolume($notGrantableServiceVolumeC);

        $serviceType = $this->createServiceType('service-type');

        $metaFileVolumeA = $this->createServiceTypeMeta('internal_file_a');
        $metaFileVolumeA->setData([
            'name'      => 'internal_file_a',
            'source'    => 'internal_file_a source',
            'target'    => 'internal_file_a target',
            'highlight' => 'internal_file_a highlight',
            'data'      => 'internal_file_a data',
            'filetype'  => Entity\ServiceVolume::FILETYPE_FILE,
            'type'      => Entity\ServiceVolume::TYPE_BIND,
        ]);

        $metaFileVolumeB = $this->createServiceTypeMeta('internal_file_b');
        $metaFileVolumeB->setData([
            'name'      => 'internal_file_b',
            'source'    => 'internal_file_b source',
            'target'    => 'internal_file_b target',
            'highlight' => 'internal_file_b highlight',
            'data'      => 'internal_file_b data',
            'filetype'  => Entity\ServiceVolume::FILETYPE_FILE,
            'type'      => Entity\ServiceVolume::TYPE_BIND,
        ]);

        $metaOtherVolumeA = $this->createServiceTypeMeta('internal_other_a');
        $metaOtherVolumeA->setData([
            'name'     => 'internal_other_a',
            'source'   => 'internal_other_a source',
            'target'   => 'internal_other_a target',
            'filetype' => Entity\ServiceVolume::FILETYPE_OTHER,
            'type'     => Entity\ServiceVolume::TYPE_BIND,
        ]);

        $metaOtherVolumeB = $this->createServiceTypeMeta('internal_other_b');
        $metaOtherVolumeB->setData([
            'name'     => 'internal_other_b',
            'source'   => 'internal_other_b source',
            'target'   => 'internal_other_b target',
            'filetype' => Entity\ServiceVolume::FILETYPE_OTHER,
            'type'     => Entity\ServiceVolume::TYPE_VOLUME,
        ]);

        $serviceType->addMeta($metaFileVolumeA)
            ->addMeta($metaFileVolumeB)
            ->addMeta($metaOtherVolumeA)
            ->addMeta($metaOtherVolumeB);

        $internalVolumesArray =  [
            'files' => [
                'internal_file_a',
                'internal_file_b',
            ],
            'other' => [
                'internal_other_a',
                'internal_other_b',
            ],
        ];

        $result = $this->volume->getForNewService($project, $serviceType, $internalVolumesArray);

        $files     = $result['files'];
        $other     = $result['other'];
        $granted   = $result['granted'];
        $grantable = $result['grantable'];

        /** @var Entity\ServiceVolume $internalFileA */
        $internalFileA = $files->get('internal_file_a');

        /** @var Entity\ServiceVolume $internalFileB */
        $internalFileB = $files->get('internal_file_b');

        /** @var Entity\ServiceVolume $internalOtherA */
        $internalOtherA = $other->get('internal_other_a');

        /** @var Entity\ServiceVolume $internalOtherB */
        $internalOtherB = $other->get('internal_other_b');

        $this->assertNull($internalFileA->getProjectVolume());
        $this->assertEquals('internal_file_a', $internalFileA->getName());
        $this->assertEquals('internal_file_a data', $internalFileA->getData());

        $this->assertNull($internalFileB->getProjectVolume());
        $this->assertEquals('internal_file_b', $internalFileB->getName());
        $this->assertEquals('internal_file_b data', $internalFileB->getData());

        $this->assertNull($internalOtherA->getProjectVolume());
        $this->assertEquals('internal_other_a', $internalOtherA->getName());
        $this->assertNull($internalOtherA->getData());

        $this->assertNull($internalOtherB->getProjectVolume());
        $this->assertEquals('internal_other_b', $internalOtherB->getName());
        $this->assertNull($internalOtherB->getData());

        $this->assertEmpty($granted->count());

        $this->assertTrue($grantable->containsKey($grantableProjectVolumeA->getName()));
        $this->assertSame($grantableProjectVolumeA, $grantable->get($grantableProjectVolumeA->getName()));
    }

    public function testGetForExistingService()
    {
        $grantedProjectVolumeA   = $this->createProjectVolume('project-volume-a');
        $grantableProjectVolumeB = $this->createProjectVolume('project-volume-b');

        $grantedServiceVolumeA   = $this->createServiceVolume('service-volume-a');
        $grantableServiceVolumeB = $this->createServiceVolume('service-volume-b');

        $serviceA = $this->createService('service-a');

        $project = $this->createProject('project');
        $project->addService($serviceA)
            ->addVolume($grantedProjectVolumeA)
            ->addVolume($grantableProjectVolumeB);

        // Grantable, to be Granted: ServiceVolume with ProjectVolume, owned by Service
        $serviceA->addVolume($grantedServiceVolumeA);
        $grantedProjectVolumeA->addServiceVolume($grantedServiceVolumeA)
            ->setOwner($serviceA);

        // Grantable, not Granted: ServiceVolume with ProjectVolume, owned by Service
        $serviceA->addVolume($grantableServiceVolumeB);
        $grantableProjectVolumeB->addServiceVolume($grantableServiceVolumeB)
            ->setOwner($serviceA);

        $serviceType = $this->createServiceType('service-type');

        $metaFileVolumeA = $this->createServiceTypeMeta('internal_file_a');
        $metaFileVolumeA->setData([
            'name'      => 'internal_file_a',
            'source'    => 'internal_file_a source',
            'target'    => 'internal_file_a target',
            'highlight' => 'internal_file_a highlight',
            'data'      => 'internal_file_a data',
            'filetype'  => Entity\ServiceVolume::FILETYPE_FILE,
            'type'      => Entity\ServiceVolume::TYPE_BIND,
        ]);

        $metaOtherVolumeA = $this->createServiceTypeMeta('internal_other_a');
        $metaOtherVolumeA->setData([
            'name'     => 'internal_other_a',
            'source'   => 'internal_other_a source',
            'target'   => 'internal_other_a target',
            'filetype' => Entity\ServiceVolume::FILETYPE_OTHER,
            'type'     => Entity\ServiceVolume::TYPE_BIND,
        ]);

        $serviceType->addMeta($metaFileVolumeA)
            ->addMeta($metaOtherVolumeA);

        ///////

        $currentService = $this->createService('current-service');
        $project->addService($currentService);

        $ownedProjectVolume = $this->createProjectVolume('owned_vol-project_volume');
        $ownedProjectVolume->setOwner($currentService);

        $internalFileA  = $this->createServiceVolume('internal_file_a');
        $currentService->addVolume($internalFileA);

        $internalOtherA = $this->createServiceVolume('internal_other_a');
        $currentService->addVolume($internalOtherA);

        // ServiceVolume file with contents, not grantable
        $ownedFile = $this->createServiceVolume('owned_file');
        $ownedFile->setSource('owned_file source')
            ->setTarget('owned_file target')
            ->setHighlight('owned_file highlight')
            ->setData('owned_file data')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
            ->setType(Entity\ServiceVolume::TYPE_BIND);
        $currentService->addVolume($ownedFile);

        // ServiceVolume other (dir), not grantable
        $ownedDir = $this->createServiceVolume('owned_dir');
        $ownedDir->setSource('owned_dir source')
            ->setTarget('owned_dir target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND);
        $currentService->addVolume($ownedDir);

        // ServiceVolume with ProjectVolume, grantable
        $ownedVol = $this->createServiceVolume('owned_vol');
        $ownedVol->setSource('owned_vol source')
            ->setTarget('owned_vol target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_VOLUME);
        $ownedProjectVolume->addServiceVolume($ownedVol);
        $currentService->addVolume($ownedVol);

        // Granted from other Servie
        $grantedServiceVolumeA_A = $this->createServiceVolume('service-volume-a-granted');
        $grantedProjectVolumeA->addServiceVolume($grantedServiceVolumeA_A);
        $currentService->addVolume($grantedServiceVolumeA_A);

        $internalVolumesArray =  [
            'files' => [
                'internal_file_a',
                'fake-meta',
            ],
            'other' => [
                'internal_other_a',
                'fake-meta',
            ],
        ];

        $result = $this->volume->getForExistingService($currentService, $serviceType, $internalVolumesArray);

        $files     = $result['files'];
        $other     = $result['other'];
        $granted   = $result['granted'];
        $grantable = $result['grantable'];

        $this->assertSame($internalFileA, $files->get('internal-file-a'));
        $this->assertSame($ownedFile, $files->get('owned-file'));

        $this->assertSame($internalOtherA, $other->get('internal-other-a'));
        $this->assertSame($ownedDir, $other->get('owned-dir'));
        $this->assertSame($ownedVol, $other->get('owned-vol-project-volume'));

        $this->assertSame($grantedServiceVolumeA_A, $granted->get('project-volume-a'));

        $this->assertSame($grantableProjectVolumeB, $grantable->get('project-volume-b'));
    }

    public function testGrantRemovesVolumesNoLongerGranted()
    {
        $project = $this->createProject('project');

        $serviceA = $this->createService('service-a');

        $serviceVolumeA = $this->createServiceVolume('service-volume-a');
        $projectVolumeA = $this->createProjectVolume('project-volume-a');
        $projectVolumeA->addServiceVolume($serviceVolumeA)
            ->setOwner($serviceA);
        $serviceA->addVolume($serviceVolumeA);

        $serviceB = $this->createService('service-b');

        $serviceVolumeB = $this->createServiceVolume('service-volume-b');
        $projectVolumeA->addServiceVolume($serviceVolumeB);
        $serviceB->addVolume($serviceVolumeB);

        $project->addService($serviceA)
            ->addService($serviceB)
            ->addVolume($projectVolumeA);

        $internalVolumesArray = [
            'files' => [],
            'other' => [],
        ];

        $volumes = $this->volume->getForExistingService(
            $serviceB,
            $this->createServiceType('service-type'),
            $internalVolumesArray
        );

        $this->assertSame($serviceVolumeB, $volumes['granted']->get('project-volume-a'));
        $this->assertTrue($serviceB->getVolumes()->contains($serviceVolumeB));

        $toGrant = [];

        $this->volume->grant($serviceB, $toGrant);

        $volumes = $this->volume->getForExistingService(
            $serviceB,
            $this->createServiceType('service-type'),
            $internalVolumesArray
        );

        $this->assertEquals(0, $volumes['granted']->count());
        $this->assertTrue($volumes['grantable']->contains($projectVolumeA));
        $this->assertFalse($serviceB->getVolumes()->contains($serviceVolumeB));
    }

    public function testGrantAddsVolumesToService()
    {
        $project = $this->createProject('project');

        $serviceA = $this->createService('service-a');

        $serviceVolumeA = $this->createServiceVolume('service-volume-a');
        $projectVolumeA = $this->createProjectVolume('project-volume-a');
        $projectVolumeA->addServiceVolume($serviceVolumeA)
            ->setOwner($serviceA);
        $serviceA->addVolume($serviceVolumeA);

        $serviceVolumeB = $this->createServiceVolume('service-volume-b');
        $projectVolumeB = $this->createProjectVolume('project-volume-b');
        $projectVolumeB->addServiceVolume($serviceVolumeB)
            ->setOwner($serviceA);
        $serviceA->addVolume($serviceVolumeB);

        $serviceB = $this->createService('service-b');

        $project->addService($serviceA)
            ->addService($serviceB)
            ->addVolume($projectVolumeA)
            ->addVolume($projectVolumeB);

        $toGrant = [
            [
                'id'     => 'project-volume-a',
                'target' => '/target',
            ],
            [
                'id'     => null,
                'target' => '/target',
            ],
            [
                'id'     => 'fake-id',
                'target' => '',
            ],
        ];

        $this->volume->grant($serviceB, $toGrant);

        $internalVolumesArray = [
            'files' => [],
            'other' => [],
        ];

        $volumes = $this->volume->getForExistingService(
            $serviceB,
            $this->createServiceType('service-type'),
            $internalVolumesArray
        );

        /** @var Entity\ServiceVolume $granted */
        $granted = $volumes['granted']->get('project-volume-a');

        $this->assertEquals(1, $volumes['granted']->count());
        $this->assertSame($granted->getProjectVolume(), $projectVolumeA);
        $this->assertTrue($serviceB->getVolumes()->contains($granted));
        $this->assertEquals('/target', $granted->getTarget());

        $this->assertEquals(1, $volumes['grantable']->count());
        $this->assertSame($projectVolumeB, $volumes['grantable']->get('project-volume-b'));
    }

    public function testSaveFileUpdatesAndCreates()
    {
        $project = $this->createProject('project');
        $service = $this->createService('service');

        $project->addService($service);

        $internalFile = $this->createServiceVolume('internal_file');
        $internalFile->setService($service)
            ->setSource('internal_file source')
            ->setTarget('internal_file target')
            ->setHighlight('internal_file highlight')
            ->setData('internal_file data')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
            ->setType(Entity\ServiceVolume::TYPE_BIND)
            ->setIsInternal(true);

        $fakeInternal = $this->createServiceVolume('fake_internal_file');
        $fakeInternal->setService($service)
            ->setSource('fake_internal_file source')
            ->setTarget('fake_internal_file target')
            ->setHighlight('fake_internal_file highlight')
            ->setData('fake_internal_file data')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
            ->setType(Entity\ServiceVolume::TYPE_BIND)
            ->setIsInternal(false);

        $ownedFile = $this->createServiceVolume('owned_file');
        $ownedFile->setService($service)
            ->setSource('owned_file source')
            ->setTarget('owned_file target')
            ->setHighlight('owned_file highlight')
            ->setData('owned_file data')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
            ->setType(Entity\ServiceVolume::TYPE_BIND);

        $deleteFile = $this->createServiceVolume('delete_file');
        $deleteFile->setService($service)
            ->setSource('delete_file source')
            ->setTarget('delete_file target')
            ->setHighlight('delete_file highlight')
            ->setData('delete_file data')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
            ->setType(Entity\ServiceVolume::TYPE_BIND);

        $internalVolumes = [
            $internalFile,
            $fakeInternal,
        ];

        $configs = [
            'internal_file' => [
                'data' => 'new data'
            ],
            'owned_file' => [
                'source' => 'owned_file source',
                'target' => 'owned_file target',
                'data'   => 'owned_file new data',
            ],
            'new_file' => [
                'source' => 'new_file source',
                'target' => 'new_file target',
                'data'   => 'new_file data',
            ],
        ];

        $this->volume->saveFile($service, $internalVolumes, $configs);

        $this->assertFalse($service->getVolumes()->contains($deleteFile));
        $this->assertEquals('new data', $internalFile->getData());
        $this->assertEquals('owned_file new data', $ownedFile->getData());

        $service->getVolumes()->removeElement($internalFile);
        $service->getVolumes()->removeElement($ownedFile);

        /** @var Entity\ServiceVolume $newFile */
        $newFile = $service->getVolumes()->first();
        $this->assertEquals('new_file data', $newFile->getData());
    }

    public function testSaveOtherUpdatesAndCreates()
    {
        $project = $this->createProject('project');
        $service = $this->createService('service');

        $project->addService($service);

        // internal TYPE_VOLUME with ProjectVolume
        $internalServiceVolumeA = $this->createServiceVolume('internal-service-volume-a');
        $internalServiceVolumeA->setService($service)
            ->setSource('internal-service-volume-a source')
            ->setTarget('internal-service-volume-a target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_VOLUME)
            ->setIsInternal(true);
        $internalProjectVolumeA = $this->createProjectVolume('internal-project-volume-a');
        $internalProjectVolumeA->setProject($project)
            ->addServiceVolume($internalServiceVolumeA)
            ->setOwner($service);

        // internal TYPE_VOLUME without ProjectVolume (ProjectVolume gets created)
        $internalServiceVolumeB = $this->createServiceVolume('internal-service-volume-b');
        $internalServiceVolumeB->setService($service)
            ->setSource('internal-service-volume-b source')
            ->setTarget('internal-service-volume-b target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_VOLUME)
            ->setIsInternal(true);

        // internal TYPE_BIND with ProjectVolume (ProjectVolume gets deleted)
        $internalServiceVolumeC = $this->createServiceVolume('internal-service-volume-c');
        $internalServiceVolumeC->setService($service)
            ->setSource('internal-service-volume-c source')
            ->setTarget('internal-service-volume-c target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND)
            ->setIsInternal(true);
        $internalProjectVolumeC = $this->createProjectVolume('internal-project-volume-c');
        $internalProjectVolumeC->setProject($project)
            ->addServiceVolume($internalServiceVolumeC)
            ->setOwner($service);

        // internal TYPE_BIND without ProjectVolume
        $internalServiceVolumeD = $this->createServiceVolume('internal-service-volume-d');
        $internalServiceVolumeD->setService($service)
            ->setSource('internal-service-volume-d source')
            ->setTarget('internal-service-volume-d target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND)
            ->setIsInternal(true);

        $fakeInternal = $this->createServiceVolume('fake_internal');
        $fakeInternal->setService($service)
            ->setSource('fake_internal source')
            ->setTarget('fake_internal target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND)
            ->setIsInternal(false);

        ////

        // not-internal TYPE_VOLUME with ProjectVolume
        $notInternalServiceVolumeA = $this->createServiceVolume('not-internal-service-volume-a');
        $notInternalServiceVolumeA->setService($service)
            ->setSource('not-internal-service-volume-a source')
            ->setTarget('not-internal-service-volume-a target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_VOLUME);
        $notInternalProjectVolumeA = $this->createProjectVolume('not-internal-project-volume-a');
        $notInternalProjectVolumeA->setProject($project)
            ->addServiceVolume($notInternalServiceVolumeA)
            ->setOwner($service);

        // not-internal TYPE_VOLUME without ProjectVolume (ProjectVolume gets created)
        $notInternalServiceVolumeB = $this->createServiceVolume('not-internal-service-volume-b');
        $notInternalServiceVolumeB->setService($service)
            ->setSource('not-internal-service-volume-b source')
            ->setTarget('not-internal-service-volume-b target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_VOLUME);

        // not-internal TYPE_BIND with ProjectVolume (ProjectVolume gets deleted)
        $notInternalServiceVolumeC = $this->createServiceVolume('not-internal-service-volume-c');
        $notInternalServiceVolumeC->setService($service)
            ->setSource('not-internal-service-volume-c source')
            ->setTarget('not-internal-service-volume-c target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND);
        $notInternalProjectVolumeC = $this->createProjectVolume('not-internal-project-volume-c');
        $notInternalProjectVolumeC->setProject($project)
            ->addServiceVolume($notInternalServiceVolumeC)
            ->setOwner($service);

        // not-internal TYPE_BIND without ProjectVolume
        $notInternalServiceVolumeD = $this->createServiceVolume('not-internal-service-volume-d');
        $notInternalServiceVolumeD->setService($service)
            ->setSource('not-internal-service-volume-d source')
            ->setTarget('not-internal-service-volume-d target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND);

        // not-internal user no longer wants
        $deleteServiceVolume = $this->createServiceVolume('delete-service-volume');
        $deleteServiceVolume->setService($service)
            ->setSource('delete-service-volume source')
            ->setTarget('delete-service-volume target')
            ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
            ->setType(Entity\ServiceVolume::TYPE_BIND);
        $deleteProjectVolumeC = $this->createProjectVolume('delete-project-volume');
        $deleteProjectVolumeC->setProject($project)
            ->addServiceVolume($deleteServiceVolume)
            ->setOwner($service);

        $serviceB = $this->createService('service-b');
        $project->addService($serviceB);

        $serviceBInternalGrant = $this->createServiceVolume('service-b-internal-grant');
        $internalProjectVolumeC->addServiceVolume($serviceBInternalGrant);

        $serviceBNotInternalGrant = $this->createServiceVolume('service-b-not-internal-grant');
        $notInternalProjectVolumeC->addServiceVolume($serviceBNotInternalGrant);

        $serviceBDeleteGrant = $this->createServiceVolume('service-b-delete-grant');
        $deleteProjectVolumeC->addServiceVolume($serviceBDeleteGrant);

        $internalVolumes = [
            $internalServiceVolumeA,
            $internalServiceVolumeB,
            $internalServiceVolumeC,
            $internalServiceVolumeD,
            $fakeInternal,
        ];

        $configs = [
            'internal-service-volume-a'     => [
                'type'   => Entity\ServiceVolume::TYPE_VOLUME,
                'source' => 'internal-service-volume-a new source',
            ],
            'internal-service-volume-b'     => [
                'type'   => Entity\ServiceVolume::TYPE_VOLUME,
                'source' => 'internal-service-volume-b new source',
            ],
            'internal-service-volume-c'     => [
                'type'   => Entity\ServiceVolume::TYPE_BIND,
                'source' => 'internal-project-volume-c new source',
            ],
            'internal-service-volume-d'     => [
                'type'   => Entity\ServiceVolume::TYPE_BIND,
                'source' => 'internal-service-volume-d new source',
            ],
            'not-internal-service-volume-a' => [
                'name'   => 'not-internal-service-volume-a new name',
                'source' => 'not-internal-service-volume-a new source',
                'target' => 'not-internal-service-volume-a new target',
                'type'   => Entity\ServiceVolume::TYPE_VOLUME,
            ],
            'not-internal-service-volume-b' => [
                'name'   => 'not-internal-service-volume-b new name',
                'source' => 'not-internal-service-volume-b new source',
                'target' => 'not-internal-service-volume-b new target',
                'type'   => Entity\ServiceVolume::TYPE_VOLUME,
            ],
            'not-internal-service-volume-c' => [
                'name'   => 'not-internal-service-volume-c new name',
                'source' => 'not-internal-service-volume-c new source',
                'target' => 'not-internal-service-volume-c new target',
                'type'   => Entity\ServiceVolume::TYPE_BIND,
            ],
            'not-internal-service-volume-d' => [
                'name'   => 'not-internal-service-volume-d new name',
                'source' => 'not-internal-service-volume-d new source',
                'target' => 'not-internal-service-volume-d new target',
                'type'   => Entity\ServiceVolume::TYPE_BIND,
            ],
            'new_bind_volume'               => [
                'name'   => 'new_bind new name',
                'source' => 'new_bind new source',
                'target' => 'new_bind new target',
                'type'   => Entity\ServiceVolume::TYPE_BIND,
            ],
            'new_volume'                    => [
                'name'   => 'new_volume new name',
                'source' => 'new_volume new source',
                'target' => 'new_volume new target',
                'type'   => Entity\ServiceVolume::TYPE_VOLUME,
            ],
        ];

        $this->volume->saveOther($service, $internalVolumes, $configs);

        $this->assertFalse($service->getVolumes()->contains($deleteServiceVolume));

        $this->assertEquals('internal-service-volume-a new source', $internalServiceVolumeA->getSource());
        $this->assertEquals('internal-service-volume-b new source', $internalServiceVolumeB->getSource());
        $this->assertEquals('internal-project-volume-c new source', $internalServiceVolumeC->getSource());
        $this->assertEquals('internal-service-volume-d new source', $internalServiceVolumeD->getSource());
        $this->assertEquals('not-internal-service-volume-a new source', $notInternalServiceVolumeA->getSource());
        $this->assertEquals('not-internal-service-volume-b new source', $notInternalServiceVolumeB->getSource());
        $this->assertEquals('not-internal-service-volume-c new source', $notInternalServiceVolumeC->getSource());
        $this->assertEquals('not-internal-service-volume-d new source', $notInternalServiceVolumeD->getSource());

        $this->assertNotNull($internalServiceVolumeA->getProjectVolume());
        $this->assertNotNull($internalServiceVolumeB->getProjectVolume());
        $this->assertNull($internalServiceVolumeC->getProjectVolume());
        $this->assertNull($internalServiceVolumeD->getProjectVolume());

        $this->assertNotNull($notInternalServiceVolumeA->getProjectVolume());
        $this->assertNotNull($notInternalServiceVolumeB->getProjectVolume());
        $this->assertNull($notInternalServiceVolumeC->getProjectVolume());
        $this->assertNull($notInternalServiceVolumeD->getProjectVolume());

        $service->getVolumes()->removeElement($internalServiceVolumeA);
        $service->getVolumes()->removeElement($internalServiceVolumeB);
        $service->getVolumes()->removeElement($internalServiceVolumeC);
        $service->getVolumes()->removeElement($internalServiceVolumeD);
        $service->getVolumes()->removeElement($notInternalServiceVolumeA);
        $service->getVolumes()->removeElement($notInternalServiceVolumeB);
        $service->getVolumes()->removeElement($notInternalServiceVolumeC);
        $service->getVolumes()->removeElement($notInternalServiceVolumeD);

        /** @var Entity\ServiceVolume $newBind */
        $newBind = $service->getVolumes()->first();
        $this->assertEquals('new_bind new source', $newBind->getSource());
        $this->assertNull($newBind->getProjectVolume());

        $service->getVolumes()->removeElement($newBind);

        /** @var Entity\ServiceVolume $newVolume */
        $newVolume = $service->getVolumes()->first();
        $this->assertEquals('new_volume new source', $newVolume->getSource());
        $this->assertNotNull($newVolume->getProjectVolume());

        $this->assertFalse($internalProjectVolumeC->getServiceVolumes()->contains($serviceBInternalGrant));
        $this->assertFalse($notInternalProjectVolumeC->getServiceVolumes()->contains($serviceBNotInternalGrant));
        $this->assertFalse($deleteProjectVolumeC->getServiceVolumes()->contains($serviceBDeleteGrant));
    }
}
