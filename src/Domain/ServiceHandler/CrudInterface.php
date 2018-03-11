<?php

namespace Dashtainer\Domain\ServiceHandler;

use Dashtainer\Entity;
use Dashtainer\Form;

interface CrudInterface
{
    public function getServiceTypeSlug() : string;

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\Service\CreateAbstract;

    public function create($form) : Entity\DockerService;

    public function getCreateParams(Entity\DockerProject $project) : array;

    public function getViewParams(Entity\DockerService $service) : array;

    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService;

    public function delete(Entity\DockerService $service);
}
