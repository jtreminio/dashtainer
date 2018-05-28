<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\NodeJs;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class NodeJsTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\NodeJsCreate */
    protected $form;

    /** @var NodeJs */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\NodeJsCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->version       = '9';
        $this->form->port          = '8080';
        $this->form->command       = 'npm run';
        $this->form->project_files = [
            'type'  => 'local',
            'local' => [
                'source' => '~/www/project',
            ]
        ];

        $this->worker = new NodeJs(
            $this->serviceRepo,
            $this->serviceTypeRepo,
            $this->networkDomain,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $versionMeta = $service->getMeta('version');
        $portMeta    = $service->getMeta('port');

        $this->assertEquals("node:{$this->form->version}", $service->getImage());
        $this->assertEquals([$this->form->port], $service->getExpose());
        $this->assertEquals([$this->form->command], $service->getCommand());
        $this->assertEquals([$this->form->version], $versionMeta->getData());
        $this->assertEquals([$this->form->port], $portMeta->getData());

        $this->assertNotNull($service->getVolume('project_files_source'));
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $versionMeta = $service->getMeta('version');
        $portMeta    = $service->getMeta('port');

        $this->assertSame(
            $versionMeta->getData()[0],
            $params['version']
        );
        $this->assertSame(
            $portMeta->getData()[0],
            $params['port']
        );
        $this->assertSame(
            $service->getCommand(),
            $params['command']
        );
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->port    = '1000';
        $form->command = 'new command';

        $updatedService = $this->worker->update($service, $form);

        $updatedPortMeta = $updatedService->getMeta('port');

        $this->assertSame(
            $updatedPortMeta->getData()[0],
            $form->port
        );
        $this->assertSame(
            $service->getCommand(),
            [$form->command]
        );
    }
}
