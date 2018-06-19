<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Adminer extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'adminer';

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\AdminerCreate();
    }

    /**
     * @param Form\Docker\Service\AdminerCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage('adminer')
            ->setEnvironments([
                'ADMINER_DESIGN'  => $form->design,
                'ADMINER_PLUGINS' => join(' ', $form->plugins),
            ])
            ->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway');

        $frontendRule = sprintf('Host:%s.%s.localhost',
            $service->getSlug(),
            $service->getProject()->getSlug()
        );

        $service->addLabel('traefik.frontend.rule', $frontendRule);

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'fileHighlight' => 'php',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $env = $service->getEnvironments();

        $design  = $env['ADMINER_DESIGN'];
        $plugins = !empty($env['ADMINER_PLUGINS'])
            ? explode(' ', $env['ADMINER_PLUGINS'])
            : [];

        $designsMeta = $service->getType()->getMeta('designs');

        $availableDesigns = [];
        $availableDesigns += $designsMeta->getData()['default'];
        $availableDesigns += $designsMeta->getData()['available'];

        if (($key = array_search($design, $availableDesigns)) !== false) {
            unset($availableDesigns[$key]);
        }

        $pluginsMeta = $service->getType()->getMeta('plugins');

        $availablePlugins = $pluginsMeta->getData()['available'];
        foreach ($plugins as $plugin) {
            if (($key = array_search($plugin, $availablePlugins)) !== false) {
                unset($availablePlugins[$key]);
            }
        }

        return array_merge(parent::getViewParams($service), [
            'design'           => $design,
            'plugins'          => $plugins,
            'availableDesigns' => $availableDesigns,
            'availablePlugins' => $availablePlugins,
            'fileHighlight'    => 'php',
        ]);
    }

    /**
     * @param Entity\Docker\Service             $service
     * @param Form\Docker\Service\AdminerCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
        $service->setEnvironments([
            'ADMINER_DESIGN'  => $form->design,
            'ADMINER_PLUGINS' => join(' ', $form->plugins),
        ]);

        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();
    }

    protected function internalNetworksArray() : array
    {
        return [
            'public',
        ];
    }

    protected function internalPortsArray() : array
    {
        return [];
    }

    protected function internalSecretsArray() : array
    {
        return [];
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [],
            'other' => [],
        ];
    }
}
