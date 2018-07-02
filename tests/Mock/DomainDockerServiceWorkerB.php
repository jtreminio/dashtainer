<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerAbstract;
use Dashtainer\Form\Docker as Form;

class DomainDockerServiceWorkerB extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mock_worker_type_b';

    /** @var FormDockerServiceCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new FormDockerServiceCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name);
    }

    public function update()
    {
    }

    public function getCreateParams() : array
    {
        return [];
    }

    public function getViewParams() : array
    {
        return [];
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
