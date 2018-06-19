<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Blackfire extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'blackfire';

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\BlackfireCreate();
    }

    /**
     * @param Form\Docker\Service\BlackfireCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage('blackfire/blackfire')
            ->setEnvironments([
                'BLACKFIRE_SERVER_ID'    => $form->server_id,
                'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
            ]);

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
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return array_merge(parent::getViewParams($service), [
        ]);
    }

    /**
     * @param Entity\Docker\Service               $service
     * @param Form\Docker\Service\BlackfireCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
        $service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
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
            'other' => [],
        ];
    }
}
