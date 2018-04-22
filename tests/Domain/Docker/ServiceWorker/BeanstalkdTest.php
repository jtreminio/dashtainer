<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Beanstalkd;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class BeanstalkdTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\BeanstalkdCreate */
    protected $form;

    /** @var Beanstalkd */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\BeanstalkdCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'Dockerfile' => 'Dockerfile contents',
        ];
        $this->form->datastore   = 'local';

        $this->worker = new Beanstalkd($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);
    }

    public function testCreateReturnsServiceEntity()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $datastoreVolume = $service->getVolume('datastore');

        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($datastoreVolume);
        $this->assertNull($datastoreVolume->getProjectVolume());

        $this->assertEquals('$PWD/service-name/datadir', $datastoreVolume->getSource());
    }

    public function testCreateReturnsServiceEntityWithNonLocalDatastore()
    {
        $this->networkRepoDefaultExpects();

        $this->form->datastore = 'volume';

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $datastore = $service->getVolume('datastore');
        $projectDatastoreVolume = $datastore->getProjectVolume();

        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($datastore);
        $this->assertNotNull($projectDatastoreVolume);

        $this->assertEquals('service-name-datastore', $projectDatastoreVolume->getName());
        $this->assertEquals($projectDatastoreVolume->getName(), $datastore->getSource());
    }

    public function testGetCreateParams()
    {
        $this->assertEquals([], $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals('local', $params['datastore']);
        $this->assertSame(
            $service->getVolume('Dockerfile'),
            $params['systemFiles']['Dockerfile']
        );
    }

    public function testUpdateDatastoreNoChangeLocalToLocal()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new Beanstalkd($this->serviceRepo, $networkRepo, $this->serviceTypeRepo);

        $form = clone $this->form;

        $form->system_file['Dockerfile'] = 'new dockerfile data';

        $updatedService = $worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();
        $uDockerfileVol       = $updatedService->getVolume('Dockerfile');

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());
        $this->assertEquals($form->system_file['Dockerfile'], $uDockerfileVol->getData());

        $this->assertNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreNoChangeVolumeToVolume()
    {
        $this->networkRepoDefaultExpects();

        $this->form->datastore = 'volume';

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new Beanstalkd($this->serviceRepo, $networkRepo, $this->serviceTypeRepo);

        $form = clone $this->form;

        $form->datastore = 'volume';

        $updatedService = $worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesLocalToVolume()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new Beanstalkd($this->serviceRepo, $networkRepo, $this->serviceTypeRepo);

        $form = clone $this->form;

        $form->datastore = 'volume';

        $updatedService = $worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesVolumeToLocal()
    {
        $this->networkRepoDefaultExpects();

        $this->form->datastore = 'volume';

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new Beanstalkd($this->serviceRepo, $networkRepo, $this->serviceTypeRepo);

        $form = clone $this->form;

        $form->datastore = 'local';

        $updatedService = $worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());

        $this->assertNull($uProjectDatastoreVol);
    }
}
