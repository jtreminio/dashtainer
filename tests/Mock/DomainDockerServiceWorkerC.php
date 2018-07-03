<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerAbstract;
use Dashtainer\Domain\Docker\ServiceWorker\WorkerParentInterface;
use Dashtainer\Domain\Docker\ServiceWorker\WorkerServiceRepoInterface;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Repository\Docker as Repository;
use Dashtainer\Tests\Mock;

class DomainDockerServiceWorkerC
    extends WorkerAbstract
    implements WorkerParentInterface, WorkerServiceRepoInterface
{
    public const SERVICE_TYPE_SLUG = 'mock_worker_type_c';

    /** @var FormDockerServiceCreate */
    protected $form;

    /** @var Repository\Service */
    protected $repo;

    public function setRepo(Repository\Service $repo)
    {
        $this->repo = $repo;
    }

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
        return [
            'param5' => 'value5',
        ];
    }

    public function getViewParams() : array
    {
        return [
            'param5' => 'value5',
            'param6' => 'value6',
        ];
    }

    public function manageChildren() : array
    {
        $data = [
            'create' => [],
            'update' => [],
            'delete' => [],
        ];

        if ($this->form->child_action === 'delete') {
            $data['delete'] []= $this->service->getChildren()->first();
        }

        if ($this->form->child_action === 'update') {
            $form = new Mock\FormDockerServiceCreate();
            $form->version = 'updated-version';

            $data['update'] []= [
                'service' => $this->service->getChildren()->first(),
                'form'    => $form,
            ];
        }

        if ($this->form->child_action === 'create') {
            $form = DomainDockerServiceWorkerA::getFormInstance();
            $form->name = 'new-child';

            $data['create'] []= [
                'serviceTypeSlug' => DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG,
                'form'            => $form,
            ];
        }

        return $data;
    }
}
