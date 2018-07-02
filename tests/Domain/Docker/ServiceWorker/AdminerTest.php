<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Adminer;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class AdminerTest extends ServiceWorkerBase
{
    /** @var Form\Service\AdminerCreate */
    protected $form;

    /** @var Adminer */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $availableDesignsMeta = $this->createServiceTypeMeta('designs')
            ->setData([
                'default'   => ['designA'],
                'available' => ['designA', 'designB'],
            ]);

        $availablePluginsMeta = $this->createServiceTypeMeta('plugins')
            ->setData([
                'available' => ['pluginA', 'pluginB'],
            ]);

        $this->serviceType->addMeta($availableDesignsMeta)
            ->addMeta($availablePluginsMeta);

        $this->form = Adminer::getFormInstance();
        $this->form->name   = 'service-name';
        $this->form->design = 'form-design';

        $this->worker = new Adminer();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $labels = $this->service->getLabels();

        $this->assertSame($this->form->name, $this->service->getName());

        $this->assertEquals('adminer', $this->service->getImage());

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

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->design  = 'new-design-choice';
        $this->form->plugins = ['new-plugin-a', 'new-plugin-b'];

        $this->worker->update();

        $environments = $this->service->getEnvironments();

        $this->assertEquals('new-design-choice', $environments['ADMINER_DESIGN']);
        $this->assertEquals('new-plugin-a new-plugin-b', $environments['ADMINER_PLUGINS']);
    }

    public function testGetCreateParams()
    {
        $this->worker->create();

        $params = $this->worker->getCreateParams();

        $this->assertEquals('designA', $params['design']);
        $this->assertEquals([], $params['plugins']);
        $this->assertEquals(['designA', 'designB'], $params['availableDesigns']);
        $this->assertEquals(['pluginA', 'pluginB'], $params['availablePlugins']);
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $params = $this->worker->getViewParams();

        $this->assertEquals($this->form->design, $params['design']);
        $this->assertEquals($this->form->plugins, $params['plugins']);
        $this->assertEquals(['designA', 'designB'], $params['availableDesigns']);
        $this->assertEquals(['pluginA', 'pluginB'], $params['availablePlugins']);
    }
}
