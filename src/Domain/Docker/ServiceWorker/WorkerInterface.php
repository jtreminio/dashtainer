<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker as Domain;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

interface WorkerInterface
{
    public const SERVICE_TYPE_SLUG = '';

    public function setServiceType(Entity\ServiceType $serviceType);

    public function setVersion(string $version = null);

    public function getServiceType() : Entity\ServiceType;

    public function setWorkerBag(Domain\WorkerBag $workerBag);

    public function getCreateForm() : Form\Service\CreateAbstract;

    public function create($form) : Entity\Service;

    public function getCreateParams(Entity\Project $project) : array;

    public function getViewParams(Entity\Service $service) : array;

    public function update(Entity\Service $service, $form);

    public function delete(Entity\Service $service);
}
