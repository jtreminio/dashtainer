<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Blackfire;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class BlackfireTest extends ServiceWorkerBase
{
    /** @var Form\Service\BlackfireCreate */
    protected $form;

    /** @var Blackfire */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = Blackfire::getFormInstance();
        $this->form->name         = 'service-name';
        $this->form->server_id    = 'server_id';
        $this->form->server_token = 'server_token';

        $this->worker = new Blackfire();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $environments = $this->service->getEnvironments();

        $this->assertEquals('blackfire/blackfire', $this->service->getImage());
        $this->assertEquals($this->form->server_id, $environments['BLACKFIRE_SERVER_ID']);
        $this->assertEquals($this->form->server_token, $environments['BLACKFIRE_SERVER_TOKEN']);
    }

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->server_id    = 'new_server_id';
        $this->form->server_token = 'new_server_token';

        $this->worker->update();

        $environments = $this->service->getEnvironments();

        $this->assertEquals($this->form->server_id, $environments['BLACKFIRE_SERVER_ID']);
        $this->assertEquals($this->form->server_token, $environments['BLACKFIRE_SERVER_TOKEN']);
    }
}
