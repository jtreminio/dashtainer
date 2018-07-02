<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

abstract class WorkerAbstract implements WorkerInterface
{
    protected $form;

    /** @var Entity\Service */
    protected $service;

    /** @var Entity\ServiceType */
    protected $serviceType;

    public function setForm(Form\Service\CreateAbstract $form)
    {
        $this->form = $form;

        return $this;
    }

    public function getForm() : Form\Service\CreateAbstract
    {
        if (!$this->form) {
            $this->form = static::getFormInstance();
        }

        return $this->form;
    }

    public function getProject(): Entity\Project
    {
        return $this->service->getProject();
    }

    public function setService(Entity\Service $service)
    {
        $this->service = $service;

        return $this;
    }

    public function getService() : Entity\Service
    {
        return $this->service;
    }

    public function setServiceType(Entity\ServiceType $serviceType)
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    public function getServiceType() : Entity\ServiceType
    {
        return $this->serviceType;
    }

    public function getInternalNetworks() : array
    {
        return [];
    }

    public function getInternalPorts() : array
    {
        return [];
    }

    public function getInternalSecrets() : array
    {
        return [];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [],
            'other' => [],
        ];
    }
}
