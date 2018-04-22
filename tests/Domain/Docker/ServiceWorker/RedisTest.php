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
        $this->networkRepoDefaultExpects();

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
        $service = $this->worker->create($this->form);

        $this->serviceRepo->expects($this->once())
            ->method('getProjectBindPorts')
            ->with($this->project)
            ->will($this->returnValue($usedPorts));

        $params = $this->worker->getViewParams($service);

        $this->assertEquals($openPort, $params['bindPort']);
    }

    public function providerGetCreateParamsReturnsFirstUnusedBindPort()
    {
        yield [
            [],
            6380
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6380]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6381]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6382]),
            ],
            6383
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6380]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6382]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([6384]),
            ],
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
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new Redis($this->serviceRepo, $networkRepo, $this->serviceTypeRepo);

        $form = clone $this->form;

        $form->port_confirm = true;
        $form->port         = 6380;

        $updatedService = $worker->update($service, $form);

        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();

        $expectedServicePorts = ["{$form->port}:6379"];

        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
    }
}
