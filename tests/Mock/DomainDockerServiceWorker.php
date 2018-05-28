<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerAbstract;
use Dashtainer\Domain\Docker\ServiceWorker\WorkerInterface;
use Dashtainer\Entity;
use Dashtainer\Form;

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
    public function createWithSecrets($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $this->createSecrets($service, $form);

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

        $this->networkDomain->addToPublicNetwork($service);
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
        $allSecrets = $this->secretDomain->getAll($project);

        return [
            'secrets' => [
                'all'       => $allSecrets,
                'internal'  => [],
                'granted'   => [],
                'grantable' => $allSecrets,
                'owned'     => [],
            ],
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $project = $service->getProject();

        return [
            'secrets' => [
                'all'       => $this->secretDomain->getAll($project),
                'internal'  => $this->secretDomain->getInternal($service),
                'owned'     => $this->secretDomain->getNotInternal($service),
                'granted'   => $this->secretDomain->getGranted($service),
                'grantable' => $this->secretDomain->getNotGranted($service),
            ],
        ];
    }

    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $this->userFilesUpdate($service, $form);

        return $service;
    }

    public function updateWithSecrets(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $this->updateSecrets($service, $form);

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

    /**
     * @param Entity\Docker\Service   $service
     * @param FormDockerServiceCreate $form
     * @return array [secret name => contents]
     */
    protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array {
        $slug = $service->getSlug();

        return [
            "{$slug}-internal_secret_1" => 'internal secret 1 contents',
            "{$slug}-internal_secret_2" => 'internal secret 2 contents',
        ];
    }
}
