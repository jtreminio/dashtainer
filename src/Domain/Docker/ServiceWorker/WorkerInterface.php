<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

interface WorkerInterface
{
    public const SERVICE_TYPE_SLUG = '';

    public static function getFormInstance() : Form\Service\CreateAbstract;

    public function setForm(Form\Service\CreateAbstract $form);

    public function getForm() : Form\Service\CreateAbstract;

    public function getProject() : Entity\Project;

    public function setService(Entity\Service $service);

    public function getService() : Entity\Service;

    public function setServiceType(Entity\ServiceType $serviceType);

    public function getServiceType() : Entity\ServiceType;

    public function create();

    public function update();

    public function getCreateParams() : array;

    public function getViewParams() : array;

    public function getInternalNetworks() : array;

    public function getInternalPorts() : array;

    public function getInternalSecrets() : array;

    public function getInternalVolumes() : array;
}
