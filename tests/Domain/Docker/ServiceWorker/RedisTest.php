<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Redis;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class RedisTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\RedisCreate */
    protected $form;

    /** @var Redis */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\RedisCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->worker = new Redis($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getMeta('version'));
        $this->assertNotNull($service->getMeta('bind-port'));
    }

    /**
     * @var array $usedPorts
     * @var int   $openPort
     * @dataProvider providerGetCreateParamsReturnsFirstUnusedBindPort
     */
    public function testGetCreateParamsReturnsFirstUnusedBindPort(array $usedPorts, int $openPort)
    {
        foreach ($usedPorts as $port) {
            $meta = new Entity\Docker\ServiceMeta();
            $meta->setName('bind-port')
                ->setData([$port]);

            $service = new Entity\Docker\Service();
            $service->addMeta($meta);

            $this->project->addService($service);
        }

        $this->form->port         = $openPort;
        $this->form->port_confirm = true;

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($openPort, $params['bindPort']);
    }

    public function providerGetCreateParamsReturnsFirstUnusedBindPort()
    {
        yield [
            [],
            6380
        ];

        yield [
            [6380, 6381, 6382],
            6383
        ];

        yield [
            [6380, 6382, 6384],
            6381
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(6380, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->port_confirm = true;
        $form->port         = 6380;

        $updatedService = $this->worker->update($service, $form);

        $uPortMeta     = $updatedService->getMeta('bind-port');
        $uServicePorts = $updatedService->getPorts();

        $expectedServicePorts = ["{$form->port}:6379"];

        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
    }
}
