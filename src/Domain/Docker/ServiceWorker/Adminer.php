<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class Adminer extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'adminer';

    /** @var Form\Service\AdminerCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\AdminerCreate();
    }

    public function create()
    {
        $project = $this->service->getProject();

        $this->service->setName($this->form->name)
            ->setImage('adminer')
            ->setEnvironments([
                'ADMINER_DESIGN'  => $this->form->design,
                'ADMINER_PLUGINS' => join(' ', $this->form->plugins),
            ])
            ->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $this->service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway');

        $frontendRule = sprintf('Host:%s.%s.localhost',
            $this->service->getSlug(),
            $project->getSlug()
        );

        $this->service->addLabel('traefik.frontend.rule', $frontendRule);
    }

    public function update()
    {
        $this->service->setEnvironments([
            'ADMINER_DESIGN'  => $this->form->design,
            'ADMINER_PLUGINS' => join(' ', $this->form->plugins),
        ]);
    }

    public function getCreateParams() : array
    {
        $designsMeta = $this->serviceType->getMeta('designs');
        $pluginsMeta = $this->serviceType->getMeta('plugins');

        $design = array_pop($designsMeta->getData()['default']);

        return [
            'design'           => $design,
            'plugins'          => [],
            'availableDesigns' => $designsMeta->getData()['available'],
            'availablePlugins' => $pluginsMeta->getData()['available'],
            'fileHighlight'    => 'php',
        ];
    }

    public function getViewParams() : array
    {
        $env = $this->service->getEnvironments();

        $design  = $env['ADMINER_DESIGN'];
        $plugins = !empty($env['ADMINER_PLUGINS'])
            ? explode(' ', $env['ADMINER_PLUGINS'])
            : [];

        $designsMeta = $this->serviceType->getMeta('designs');

        $availableDesigns = [];
        $availableDesigns += $designsMeta->getData()['default'];
        $availableDesigns += $designsMeta->getData()['available'];

        if (($key = array_search($design, $availableDesigns)) !== false) {
            unset($availableDesigns[$key]);
        }

        $pluginsMeta = $this->serviceType->getMeta('plugins');

        $availablePlugins = $pluginsMeta->getData()['available'];
        foreach ($plugins as $plugin) {
            if (($key = array_search($plugin, $availablePlugins)) !== false) {
                unset($availablePlugins[$key]);
            }
        }

        return [
            'design'           => $design,
            'plugins'          => $plugins,
            'availableDesigns' => $availableDesigns,
            'availablePlugins' => $availablePlugins,
            'fileHighlight'    => 'php',
        ];
    }

    public function getInternalNetworks() : array
    {
        return [
            'public',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [],
            'other' => [],
        ];
    }
}
