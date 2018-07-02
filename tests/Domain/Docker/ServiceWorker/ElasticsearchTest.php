<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Elasticsearch;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class ElasticsearchTest extends ServiceWorkerBase
{
    /** @var Form\Service\ElasticsearchCreate */
    protected $form;

    /** @var Elasticsearch */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = Elasticsearch::getFormInstance();
        $this->form->name      = 'service-name';
        $this->form->version   = '1.2';
        $this->form->heap_size = '1m';

        $this->worker = new Elasticsearch();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $environment = $this->service->getEnvironments();

        $this->assertEquals(
            'docker.elastic.co/elasticsearch/elasticsearch-oss:1.2',
            $this->service->getImage()
        );

        $this->assertEquals('-Xms1m -Xmx1m', $environment['ES_JAVA_OPTS']);
        $this->assertEquals(
            'docker.elastic.co/elasticsearch/elasticsearch-oss:1.2',
            $this->service->getImage()
        );
    }

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->heap_size = '5m';

        $this->worker->update();

        $uHeapsizeMeta = $this->service->getMeta('heap_size');
        $uEnvironments = $this->service->getEnvironments();

        $expectedEnvironments = [
            'ES_JAVA_OPTS' => "-Xms5m -Xmx5m",
        ];

        $this->assertEquals(['5m'], $uHeapsizeMeta->getData());
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $params = $this->worker->getViewParams();

        $this->assertEquals($this->form->heap_size, $params['heap_size']);
    }
}
