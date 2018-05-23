<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Blackfire;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class BlackfireTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\BlackfireCreate */
    protected $form;

    /** @var Blackfire */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\BlackfireCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->server_id    = 'server_id';
        $this->form->server_token = 'server_token';

        $this->worker = new Blackfire(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $environments = $service->getEnvironments();

        $this->assertEquals('blackfire/blackfire', $service->getImage());
        $this->assertEquals($this->form->server_id, $environments['BLACKFIRE_SERVER_ID']);
        $this->assertEquals($this->form->server_token, $environments['BLACKFIRE_SERVER_TOKEN']);
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->server_id    = 'new_server_id';
        $form->server_token = 'new_server_token';

        $updatedService = $this->worker->update($service, $form);

        $environments = $updatedService->getEnvironments();

        $this->assertEquals($form->server_id, $environments['BLACKFIRE_SERVER_ID']);
        $this->assertEquals($form->server_token, $environments['BLACKFIRE_SERVER_TOKEN']);
    }
}
