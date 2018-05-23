<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Adminer extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'adminer';
    }

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
            ->setProject($form->project);

        $service->setImage('adminer');

        $service->setEnvironments([
            'ADMINER_DESIGN'  => $form->design,
            'ADMINER_PLUGINS' => join(' ', $form->plugins),
        ]);

        $publicNetwork = $this->networkRepo->getPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($publicNetwork);

        $this->serviceRepo->save($service, $publicNetwork);

        $this->addToPrivateNetworks($service, $form);

        $frontendRule = sprintf('Host:%s.%s.localhost',
            $service->getSlug(),
            $service->getProject()->getSlug()
        );

        $service->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', $frontendRule);

        $this->serviceRepo->save($service);

        $this->userFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [
            'secrets' => $this->getCreateSecrets($project),
        ];
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

        $userFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'design'           => $design,
            'plugins'          => $plugins,
            'availableDesigns' => $availableDesigns,
            'availablePlugins' => $availablePlugins,
            'userFiles'        => $userFiles,
        ];
    }

    /**
     * @param Entity\Docker\Service             $service
     * @param Form\Docker\Service\AdminerCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setEnvironments([
            'ADMINER_DESIGN'  => $form->design,
            'ADMINER_PLUGINS' => join(' ', $form->plugins),
        ]);

        $this->addToPrivateNetworks($service, $form);

        $this->userFilesUpdate($service, $form);

        return $service;
    }

    protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array {
        return [];
    }
}
