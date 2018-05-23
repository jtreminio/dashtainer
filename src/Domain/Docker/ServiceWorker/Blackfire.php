<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Blackfire extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'blackfire';
    }

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
            ->setImage('blackfire/blackfire');

        $service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
        ]);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

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
        return [];
    }

    /**
     * @param Entity\Docker\Service               $service
     * @param Form\Docker\Service\BlackfireCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
        ]);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        return $service;
    }

    protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array {
        return [];
    }
}
