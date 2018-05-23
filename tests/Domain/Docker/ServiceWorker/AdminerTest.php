<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Adminer;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class AdminerTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\AdminerCreate */
    protected $form;

    /** @var Adminer */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $availableDesignsMeta = new Entity\Docker\ServiceTypeMeta();
        $availableDesignsMeta->setName('designs')
            ->setData([
                'default'   => ['designA'],
                'available' => ['designA', 'designB'],
            ]);

        $availablePluginsMeta = new Entity\Docker\ServiceTypeMeta();
        $availablePluginsMeta->setName('plugins')
            ->setData([
                'available' => ['pluginA', 'pluginB'],
            ]);

        $this->serviceType->addMeta($availableDesignsMeta)
            ->addMeta($availablePluginsMeta);

        $this->form = new Form\Docker\Service\AdminerCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->design = 'form-design';

        $this->worker = new Adminer(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $labels = $service->getLabels();

        $this->assertSame($this->form->name, $service->getName());
        $this->assertSame($this->form->type, $service->getType());
        $this->assertSame($this->form->project, $service->getProject());

        $this->assertEquals('adminer', $service->getImage());

        $expectedTraefikBackendLabel       = '{$COMPOSE_PROJECT_NAME}_service-name';
        $expectedTraefikDockerNetworkLabel = 'traefik_webgateway';
        $expectedTraefikFrontendRuleLabel  = 'Host:service-name.projectname.localhost';
        $this->assertEquals($expectedTraefikBackendLabel, $labels['traefik.backend']);
        $this->assertEquals(
            $expectedTraefikDockerNetworkLabel,
            $labels['traefik.docker.network']
        );
        $this->assertEquals(
            $expectedTraefikFrontendRuleLabel,
            $labels['traefik.frontend.rule']
        );
    }

    public function testGetViewParams()
    {
        $userFileA = [
            'filename' => 'user file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $this->form->user_file = [$userFileA];

        $service = $this->worker->create($this->form);
        $params = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->design, $params['design']);
        $this->assertEquals($this->form->plugins, $params['plugins']);
        $this->assertEquals(['designA', 'designB'], $params['availableDesigns']);
        $this->assertEquals(['pluginA', 'pluginB'], $params['availablePlugins']);

        $this->assertCount(1, $params['userFiles']);

        $this->assertSame(
            $service->getVolume('userfilea.txt'),
            array_pop($params['userFiles'])
        );
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->design   = 'new-design-choice';
        $form->plugins  = ['new-plugin-a', 'new-plugin-b'];

        $form->user_file = [
            'userfilea_ID' => [
                'filename' => 'user file a_updated.txt',
                'target'   => '/etc/foo/updated/path',
                'data'     => 'updated text!',
            ],
            'userfilec_ID' => [
                'filename' => 'user file c.txt',
                'target'   => '/etc/foo/new/file',
                'data'     => 'new file!',
            ],
        ];

        $updatedService = $this->worker->update($service, $form);

        $environments = $updatedService->getEnvironments();

        $this->assertEquals('new-design-choice', $environments['ADMINER_DESIGN']);
        $this->assertEquals('new-plugin-a new-plugin-b', $environments['ADMINER_PLUGINS']);

        $fileA = $updatedService->getVolume('userfilea_updated.txt');
        $fileC = $updatedService->getVolume('userfilec.txt');

        $this->assertEquals('/etc/foo/updated/path', $fileA->getTarget());
        $this->assertEquals('updated text!', $fileA->getData());

        $this->assertEquals('/etc/foo/new/file', $fileC->getTarget());
        $this->assertEquals('new file!', $fileC->getData());

        $this->assertNull($updatedService->getVolume('userfileb.txt'));
    }
}
