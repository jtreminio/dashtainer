<?php

namespace Dashtainer\Domain\Handler;

use Dashtainer\Entity;
use Dashtainer\Form;

interface CrudInterface
{
    public function create($form) : Entity\DockerService;

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\DockerServiceCreateAbstract;

    public function getViewParams(Entity\DockerService $service) : array;

    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService;

    public function delete(Entity\DockerService $service);
}
