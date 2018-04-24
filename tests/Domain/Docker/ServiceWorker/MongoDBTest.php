<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MongoDB;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class MongoDBTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\MongoDBCreate */
    protected $form;

    /** @var MongoDB */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\MongoDBCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->worker = new MongoDB($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);
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
            27018
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27018]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27019]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27020]),
            ],
            27021
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27018]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27020]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([27022]),
            ],
            27019
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(27018, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->port_confirm = true;
        $form->port         = 27018;

        $updatedService = $this->worker->update($service, $form);

        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();

        $expectedServicePorts = ["{$form->port}:27017"];

        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
    }
}
