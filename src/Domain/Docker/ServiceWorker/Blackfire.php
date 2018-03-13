<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Blackfire extends WorkerAbstract implements WorkerInterface
{
    /** @var Repository\Docker\Network */
    protected $repoDockNetwork;

    public function __construct(
        Repository\Docker\Service $repoDockService,
        Repository\Docker\Network $repoDockNetwork
    ) {
        $this->repoDockService = $repoDockService;
        $this->repoDockNetwork = $repoDockNetwork;
    }

    public function getServiceTypeSlug() : string
    {
        return 'blackfire';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
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

        $privateNetwork = $this->repoDockNetwork->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->repoDockService->save($service, $privateNetwork);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return [];
    }

    /**
     * @param Entity\Docker\Service        $service
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

        $this->repoDockService->save($service);

        return $service;
    }
}
