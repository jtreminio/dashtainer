<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Tests\Mock\DomainDockerServiceWorker;
use Dashtainer\Tests\Mock\FormDockerServiceCreate;
use Dashtainer\Entity;
use Dashtainer\Form;

class ServiceWorkerTest extends ServiceWorkerBase
{
    /** @var FormDockerServiceCreate */
    protected $form;

    /** @var DomainDockerServiceWorker */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new FormDockerServiceCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->datastore = 'local';

        $this->worker = new DomainDockerServiceWorker(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo
        );
    }

    public function testCreateReturnsServiceEntityWithNoPrivateNetworks()
    {
        $this->form->networks = [];

        $service = $this->worker->createWithPublicNetwork($this->form);

        $this->assertCount(1, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNewAndExistingPrivateNetworks()
    {
        $this->form->networks = [
            'new-network-a',
            'new-network-b',
            'private-network-a',
        ];

        $service = $this->worker->createWithPublicNetwork($this->form);

        $networks = [];
        foreach ($service->getNetworks() as $network) {
            $networks []= $network->getName();
        }

        $this->assertCount(4, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains('new-network-a', $networks);
        $this->assertContains('new-network-b', $networks);
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNewPrivateNetworks()
    {
        $this->form->networks = [
            'new-network-a',
            'new-network-b',
        ];

        $service = $this->worker->createWithPublicNetwork($this->form);

        $networks = [];
        foreach ($service->getNetworks() as $network) {
            $networks []= $network->getName();
        }

        $this->assertCount(3, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains('new-network-a', $networks);
        $this->assertContains('new-network-b', $networks);

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithExistingPrivateNetworks()
    {
        $this->form->networks = [
            'private-network-a',
        ];

        $service = $this->worker->createWithPublicNetwork($this->form);

        $this->assertCount(2, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNoUserFiles()
    {
        $this->form->user_file = [];

        $service = $this->worker->create($this->form);

        $this->assertEmpty($service->getVolumes());
    }

    public function testCreateReturnsServiceEntityWithUserFiles()
    {
        $userFileA = [
            'filename' => 'user file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $userFileB = [
            'filename' => 'user file b.txt',
            'target'   => '/etc/foo/bam',
            'data'     => 'everyone admires you!',
        ];

        $this->form->user_file = [$userFileA, $userFileB];

        $service = $this->worker->create($this->form);

        $fileA = $service->getVolume('userfilea.txt');
        $fileB = $service->getVolume('userfileb.txt');

        $expectedSourceA = '$PWD/service-name/userfilea.txt';
        $this->assertEquals($expectedSourceA, $fileA->getSource());
        $this->assertEquals($userFileA['target'], $fileA->getTarget());
        $this->assertEquals($userFileA['data'], $fileA->getData());

        $expectedSourceA = '$PWD/service-name/userfileb.txt';
        $this->assertEquals($expectedSourceA, $fileB->getSource());
        $this->assertEquals($userFileB['target'], $fileB->getTarget());
        $this->assertEquals($userFileB['data'], $fileB->getData());
    }

    public function testCreateReturnsServiceEntityWithLocalDatastore()
    {
        $service = $this->worker->createWithDatastore($this->form);

        $datastoreVolume = $service->getVolume('datastore');

        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($datastoreVolume);
        $this->assertNull($datastoreVolume->getProjectVolume());

        $this->assertEquals('$PWD/service-name/datadir', $datastoreVolume->getSource());
    }

    public function testCreateReturnsServiceEntityWithNonLocalDatastore()
    {
        $this->form->datastore = 'volume';

        $service = $this->worker->createWithDatastore($this->form);

        $datastore = $service->getVolume('datastore');
        $projectDatastoreVolume = $datastore->getProjectVolume();

        $this->assertNotNull($datastore);
        $this->assertNotNull($projectDatastoreVolume);

        $this->assertEquals('service-name-datastore', $projectDatastoreVolume->getName());
        $this->assertEquals($projectDatastoreVolume->getName(), $datastore->getSource());
    }

    public function testUpdateReturnsServiceEntityWithNoPrivateNetworks()
    {
        $this->form->networks = [
            'private-network-a',
            'private-network-b',
        ];

        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->networks = [];

        $updatedService = $this->worker->update($service, $form);

        $this->assertEmpty($updatedService->getNetworks());
    }

    public function testUpdateReturnsServiceEntityWithLessPrivateNetworks()
    {
        $this->form->networks = [
            'private-network-a',
            'private-network-b',
        ];

        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->networks = [
            'private-network-a',
        ];

        $updatedService = $this->worker->update($service, $form);

        $this->assertCount(1, $updatedService->getNetworks());
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $updatedService->getNetworks()
        );
        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $updatedService->getNetworks()
        );
    }

    public function testUpdateReturnsServiceEntityWithMorePrivateNetworks()
    {
        $this->form->networks = [
            'private-network-a',
            'private-network-b',
        ];

        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->networks = [
            'private-network-a',
            'private-network-b',
            'private-network-c',
        ];

        $updatedService = $this->worker->update($service, $form);

        $this->assertCount(3, $updatedService->getNetworks());
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $updatedService->getNetworks()
        );
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-b'],
            $updatedService->getNetworks()
        );
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-c'],
            $updatedService->getNetworks()
        );
    }

    public function testUpdateDatastoreNoChangeLocalToLocal()
    {
        $service = $this->worker->createWithDatastore($this->form);

        $form = clone $this->form;

        $updatedService = $this->worker->updateWithDatastore($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());

        $this->assertNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreNoChangeVolumeToVolume()
    {
        $this->form->datastore = 'volume';

        $service = $this->worker->createWithDatastore($this->form);

        $form = clone $this->form;

        $form->datastore = 'volume';

        $updatedService = $this->worker->updateWithDatastore($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesLocalToVolume()
    {
        $service = $this->worker->createWithDatastore($this->form);

        $form = clone $this->form;

        $form->datastore = 'volume';

        $updatedService = $this->worker->updateWithDatastore($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesVolumeToLocal()
    {
        $this->form->datastore = 'volume';

        $service = $this->worker->createWithDatastore($this->form);

        $form = clone $this->form;

        $form->datastore = 'local';

        $updatedService = $this->worker->updateWithDatastore($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());

        $this->assertNull($uProjectDatastoreVol);
    }
}
