<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

interface WorkerInterface
{
    public function getServiceTypeSlug() : string;

    public function getCreateForm() : Form\Docker\Service\CreateAbstract;

    public function create($form) : Entity\Docker\Service;

    public function getCreateParams(Entity\Docker\Project $project) : array;

    public function getViewParams(Entity\Docker\Service $service) : array;

    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service;

    public function delete(Entity\Docker\Service $service);
}
