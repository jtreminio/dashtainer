<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Tests\Mock\DomainDockerServiceWorker;
use Dashtainer\Tests\Mock\FormDockerServiceCreate;
use Dashtainer\Entity;

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
            $this->serviceTypeRepo,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntityWithNoOwnedSecrets()
    {
        $this->form->owned_secrets = [];

        $service = $this->worker->create($this->form);

        $this->assertEmpty($service->getSecrets());
    }

    public function testCreateReturnsServiceEntityWithInternalOwnedSecrets()
    {
        $this->form->owned_secrets = [];

        $service = $this->worker->createWithSecrets($this->form);

        $slug = $service->getSlug();

        $this->assertCount(2, $service->getSecrets());

        $internalProjectSecret1 = $service->getSecret("{$slug}-internal_secret_1")->getProjectSecret();
        $internalProjectSecret2 = $service->getSecret("{$slug}-internal_secret_2")->getProjectSecret();

        $this->assertNotEmpty($internalProjectSecret1);
        $this->assertNotEmpty($internalProjectSecret2);

        $this->assertEquals('internal secret 1 contents', $internalProjectSecret1->getContents());
        $this->assertEquals('internal secret 2 contents', $internalProjectSecret2->getContents());
    }

    public function testCreateReturnsServiceEntityWithNotInternalOwnedSecrets()
    {
        $this->form->owned_secrets = [
            [
                'name'     => 'not-internal-secret1',
                'contents' => 'not-internal-secret1 contents',
            ],
            [
                'name'     => 'not-internal-secret2',
                'contents' => 'not-internal-secret2 contents',
            ],
        ];

        $service = $this->worker->createWithSecrets($this->form);

        $slug = $service->getSlug();

        $this->assertCount(4, $service->getSecrets());

        $internalProjectSecret1    = $service->getSecret("{$slug}-internal_secret_1")->getProjectSecret();
        $internalProjectSecret2    = $service->getSecret("{$slug}-internal_secret_2")->getProjectSecret();
        $notInternalProjectSecret1 = $service->getSecret('not-internal-secret1')->getProjectSecret();
        $notInternalProjectSecret2 = $service->getSecret('not-internal-secret2')->getProjectSecret();

        $this->assertNotEmpty($internalProjectSecret1);
        $this->assertNotEmpty($internalProjectSecret2);
        $this->assertNotEmpty($notInternalProjectSecret1);
        $this->assertNotEmpty($notInternalProjectSecret2);

        $this->assertEquals('internal secret 1 contents', $internalProjectSecret1->getContents());
        $this->assertEquals('internal secret 2 contents', $internalProjectSecret2->getContents());
        $this->assertEquals('not-internal-secret1 contents', $notInternalProjectSecret1->getContents());
        $this->assertEquals('not-internal-secret2 contents', $notInternalProjectSecret2->getContents());
    }

    public function testCreateReturnsServiceEntityWithNoGrantedSecrets()
    {
        $this->form->grant_secrets = [];

        $service = $this->worker->createWithSecrets($this->form);

        $slug = $service->getSlug();

        $this->assertCount(2, $service->getSecrets());

        $internalProjectSecret1 = $service->getSecret("{$slug}-internal_secret_1")->getProjectSecret();
        $internalProjectSecret2 = $service->getSecret("{$slug}-internal_secret_2")->getProjectSecret();

        $this->assertNotEmpty($internalProjectSecret1);
        $this->assertNotEmpty($internalProjectSecret2);
    }

    public function testCreateReturnsServiceEntityWithGrantedSecrets()
    {
        $this->form->grant_secrets = [
            [
                // name: other project secret 1
                'id'     => 'other-project-secret-1-id',
                'target' => 'other-project-secret-1-target',
            ],
            [
                'id'     => 'invalid-id',
                'target' => 'invalid-id-target',
            ],
        ];

        $service = $this->worker->createWithSecrets($this->form);

        $this->assertCount(3, $service->getSecrets());

        $grantServiceSecret1 = $service->getSecret('other project secret 1');
        $grantProjectSecret1 = $grantServiceSecret1->getProjectSecret();

        $this->assertEquals('other-project-secret-1-target', $grantServiceSecret1->getTarget());

        $this->assertNotEmpty($grantProjectSecret1);
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

    public function testUpdateReturnsServiceEntityWithNoNotInternalOwnedSecrets()
    {
        $this->form->owned_secrets = [
            [
                'name'     => 'not-internal-secret1',
                'contents' => 'not-internal-secret1 contents',
            ],
            [
                'name'     => 'not-internal-secret2',
                'contents' => 'not-internal-secret2 contents',
            ],
        ];

        $service = $this->worker->createWithSecrets($this->form);

        $form = clone $this->form;

        $form->owned_secrets = [];

        $updatedService = $this->worker->updateWithSecrets($service, $form);

        $this->assertCount(2, $updatedService->getSecrets());
    }

    public function testUpdateReturnsServiceEntityWithRemovedAndUpdatedNotInternalOwnedSecrets()
    {
        $this->form->owned_secrets = [
            [
                'name'     => 'not-internal-secret1',
                'contents' => 'not-internal-secret1 contents',
            ],
            [
                'name'     => 'not-internal-secret2',
                'contents' => 'not-internal-secret2 contents',
            ],
        ];

        $service = $this->worker->createWithSecrets($this->form);

        $this->assertCount(4, $service->getSecrets());

        $form = clone $this->form;

        $form->owned_secrets = [
            [
                'name'     => 'not-internal-secret1',
                'contents' => 'new contents',
            ],
            [
                'name'     => 'not-internal-secret3',
                'contents' => 'not-internal-secret3 contents',
            ],
        ];

        $updatedService = $this->worker->updateWithSecrets($service, $form);

        $this->assertCount(4, $updatedService->getSecrets());

        $grantServiceSecret1 = $updatedService->getSecret('not-internal-secret1');
        $grantServiceSecret2 = $updatedService->getSecret('not-internal-secret2');
        $grantServiceSecret3 = $updatedService->getSecret('not-internal-secret3');

        $this->assertNull($grantServiceSecret2);

        $grantProjectSecret1 = $grantServiceSecret1->getProjectSecret();
        $grantProjectSecret3 = $grantServiceSecret3->getProjectSecret();

        $this->assertEquals('new contents', $grantProjectSecret1->getContents());
        $this->assertEquals('not-internal-secret3 contents', $grantProjectSecret3->getContents());
    }

    public function testUpdateReturnsServiceEntityWithNoGrantedSecrets()
    {
        $this->form->grant_secrets = [
            [
                // name: other project secret 1
                'id'     => 'other-project-secret-1-id',
                'target' => 'other-project-secret-1-target',
            ],
        ];

        $service = $this->worker->createWithSecrets($this->form);

        $this->assertCount(3, $service->getSecrets());

        $form = clone $this->form;

        $form->grant_secrets = [];

        $updatedService = $this->worker->updateWithSecrets($service, $form);

        $this->assertCount(2, $updatedService->getSecrets());

        $this->assertNull($updatedService->getSecret('other project secret 1'));
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
