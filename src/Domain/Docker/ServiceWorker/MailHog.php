<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class MailHog extends WorkerAbstract implements WorkerInterface
{
    /** @var Repository\Docker\Network */
    protected $networkRepo;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\Network $networkRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->networkRepo = $networkRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'mailhog';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\MailHogCreate();
    }

    /**
     * @param Form\Docker\Service\MailHogCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $service->setImage('mailhog/mailhog:latest');

        $publicNetwork = $this->networkRepo->getPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($publicNetwork);

        $this->serviceRepo->save($service, $publicNetwork);

        $this->addToPrivateNetworks($service, $form);

        $service->addLabel('traefik.backend', $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', "Host:{$service->getName()}.localhost")
            ->addLabel('traefik.port', 8025);

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
     * @param Entity\Docker\Service             $service
     * @param Form\Docker\Service\MailHogCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        return $service;
    }
}
