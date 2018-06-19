<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class NodeJs extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'node-js';

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\NodeJsCreate();
    }

    /**
     * @param Form\Docker\Service\NodeJsCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage("node:{$form->version}")
            ->setVersion($form->version)
            ->setExpose([$form->port])
            ->setCommand([$form->command])
            ->setWorkingDir('/var/www');

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('port')
            ->setData([$form->port])
            ->setService($service);

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $this->serviceRepo->persist($service, $portMeta);
        $this->serviceRepo->flush();

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'fileHighlight' => 'ini',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $portMeta = $service->getMeta('port');

        return array_merge(parent::getViewParams($service), [
            'port'          => $portMeta->getData()[0],
            'command'       => $service->getCommand(),
            'fileHighlight' => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service            $service
     * @param Form\Docker\Service\NodeJsCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
        $service->setExpose([$form->port])
            ->setCommand([$form->command]);

        $portMeta = $service->getMeta('port');
        $portMeta->setData([$form->port]);

        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->persist($service, $portMeta);
        $this->serviceRepo->flush();
    }

    protected function internalNetworksArray() : array
    {
        return [];
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
            'other' => [
                'root',
            ],
        ];
    }
}
