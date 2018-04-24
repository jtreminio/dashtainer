<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerAbstract;
use Dashtainer\Domain\Docker\ServiceWorker\WorkerInterface;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class DomainDockerServiceWorker extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'mock_worker';
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new FormDockerServiceCreate();
    }

    /**
     * @param FormDockerServiceCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $this->addToPrivateNetworks($service, $form);

        $this->userFilesCreate($service, $form);

        return $service;
    }

    /**
     * @param FormDockerServiceCreate $form
     * @return Entity\Docker\Service
     */
    public function createWithPublicNetwork($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $publicNetwork = $this->networkRepo->getPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($publicNetwork);

        $this->addToPrivateNetworks($service, $form);

        $this->userFilesCreate($service, $form);

        return $service;
    }

    /**
     * @param FormDockerServiceCreate $form
     * @return Entity\Docker\Service
     */
    public function createWithDatastore($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $this->addToPrivateNetworks($service, $form);

        $this->userFilesCreate($service, $form);

        $this->createDatastore($service, $form, '/path/to/dir');

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

    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $this->userFilesUpdate($service, $form);

        return $service;
    }

    /**
     * @param Entity\Docker\Service   $service
     * @param FormDockerServiceCreate $form
     * @return Entity\Docker\Service
     */
    public function updateWithDatastore(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $this->userFilesUpdate($service, $form);

        $this->updateDatastore($service, $form);

        return $service;
    }
}
