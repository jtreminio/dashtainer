<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerAbstract;
use Dashtainer\Form\Docker as Form;

class DomainDockerServiceWorkerA extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mock_worker_type_a';

    /** @var FormDockerServiceCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new FormDockerServiceCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setVersion($this->form->version);
    }

    public function update()
    {
        $this->service->setVersion($this->form->version);
    }

    public function getCreateParams() : array
    {
        return [
            'param1' => 'value1',
        ];
    }

    public function getViewParams() : array
    {
        return [
            'param1' => 'value1',
            'param2' => 'value2',
        ];
    }

    public function getInternalPorts() : array
    {
        return [
            [null, 123, 'tcp']
        ];
    }
}
