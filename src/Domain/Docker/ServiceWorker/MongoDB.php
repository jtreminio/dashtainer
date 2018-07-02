<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class MongoDB extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mongodb';

    /** @var Form\Service\MongoDBCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\MongoDBCreate();
    }

    public function create()
    {
        $version = (string) number_format($this->form->version, 1);

        $this->service->setName($this->form->name)
            ->setImage("mongo:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Service::RESTART_ALWAYS);

        $this->form->secrets['mongodb_host']['data'] = $this->service->getSlug();
    }

    public function update()
    {
    }

    public function getCreateParams() : array
    {
        return [
            'fileHighlight' => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        return [
            'fileHighlight' => 'ini',
        ];
    }

    public function getInternalPorts() : array
    {
        return [
            [null, 27017, 'tcp']
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'mongodb_host',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [],
            'other' => [
                'datadir'
            ],
        ];
    }
}
