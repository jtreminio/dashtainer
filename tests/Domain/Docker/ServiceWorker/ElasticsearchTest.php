<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Elasticsearch;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class ElasticsearchTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\ElasticsearchCreate */
    protected $form;

    /** @var Elasticsearch */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\ElasticsearchCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'elasticsearch.yml' => 'elasticsearch.yml contents',
        ];
        $this->form->datastore = 'local';
        $this->form->version   = '1.2';
        $this->form->heap_size = '1m';

        $this->worker = new Elasticsearch(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $environment = $service->getEnvironments();

        $this->assertEquals(
            'docker.elastic.co/elasticsearch/elasticsearch-oss:1.2',
            $service->getImage()
        );

        $this->assertEquals('-Xms1m -Xmx1m', $environment['ES_JAVA_OPTS']);

        $datastoreVolume = $service->getVolume('datastore');
        $esVolume        = $service->getVolume('elasticsearch.yml');

        $this->assertNotNull($esVolume);
        $this->assertNotNull($datastoreVolume);
        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getMeta('version'));
        $this->assertNotNull($service->getMeta('heap_size'));

        $this->assertEquals('$PWD/service-name/datadir', $datastoreVolume->getSource());
        $this->assertEquals($this->form->system_file['elasticsearch.yml'], $esVolume->getData());
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals($this->form->datastore, $params['datastore']);
        $this->assertEquals($this->form->heap_size, $params['heap_size']);
        $this->assertSame(
            $service->getVolume('elasticsearch.yml'),
            $params['systemFiles']['elasticsearch.yml']
        );
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->system_file['elasticsearch.yml'] = 'new elasticsearch.yml data';
        $form->heap_size = '5m';

        $updatedService = $this->worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uEsVol               = $updatedService->getVolume('elasticsearch.yml');
        $uHeapsizeMeta        = $updatedService->getMeta('heap_size');
        $uEnvironments        = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'ES_JAVA_OPTS' => "-Xms5m -Xmx5m",
        ];

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(['5m'], $uHeapsizeMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());
        $this->assertEquals($form->system_file['elasticsearch.yml'], $uEsVol->getData());
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
