<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\NodeJs;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class NodeJsTest extends ServiceWorkerBase
{
    /** @var Form\Service\NodeJsCreate */
    protected $form;

    /** @var NodeJs */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = NodeJs::getFormInstance();
        $this->form->name    = 'service-name';
        $this->form->version = '1.2';
        $this->form->port    = '8080';
        $this->form->command = 'npm run';

        $this->worker = new NodeJs();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $portMeta = $this->service->getMeta('port');

        $this->assertEquals("node:{$this->form->version}", $this->service->getImage());
        $this->assertEquals([$this->form->port], $this->service->getExpose());
        $this->assertEquals([$this->form->command], $this->service->getCommand());
        $this->assertEquals([$this->form->port], $portMeta->getData());

        $this->assertEquals('node:1.2', $this->service->getImage());
    }

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->port    = '1000';
        $this->form->command = 'new command';

        $this->worker->update();

        $portMeta = $this->service->getMeta('port');

        $this->assertSame(
            $portMeta->getData()[0],
            $this->form->port
        );
        $this->assertSame(
            $this->service->getCommand(),
            [$this->form->command]
        );
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $params = $this->worker->getViewParams();

        $portMeta = $this->service->getMeta('port');

        $this->assertEquals($portMeta->getData()[0], $params['port']);
        $this->assertEquals($this->service->getCommand(), $params['command']);
    }
}
