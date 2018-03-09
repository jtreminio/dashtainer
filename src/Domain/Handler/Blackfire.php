<?php

namespace Dashtainer\Domain\Handler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Blackfire implements CrudInterface
{
    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var Repository\DockerServiceRepository */
    protected $serviceRepo;

    public function __construct(
        Repository\DockerServiceRepository $serviceRepo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->networkRepo = $networkRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'blackfire';
    }

    /**
     * @param Form\DockerServiceCreate\Blackfire $form
     * @return Entity\DockerService
     */
    public function create($form) : Entity\DockerService
    {
        $service = new Entity\DockerService();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage('blackfire/blackfire');

        $service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
        ]);

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->serviceRepo->save($service, $privateNetwork);

        return $service;
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\DockerServiceCreateAbstract {
        return new Form\DockerServiceCreate\Blackfire();
    }

    public function getCreateParams(Entity\DockerProject $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        return [];
    }

    /**
     * @param Entity\DockerService               $service
     * @param Form\DockerServiceCreate\Blackfire $form
     * @return Entity\DockerService
     */
    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService {
        $service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $form->server_token,
        ]);

        $this->serviceRepo->save($service);

        return $service;
    }

    public function delete(Entity\DockerService $service)
    {
        if ($parent = $service->getParent()) {
            $parent->removeChild($service);
        }

        $this->serviceRepo->delete($service);
    }
}
