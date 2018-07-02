<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class Redis extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'redis';

    /** @var Form\Service\RedisCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\RedisCreate();
    }

    public function create()
    {
        $version = (string) number_format($this->form->version, 1);

        $this->service->setName($this->form->name)
            ->setImage("redis:{$version}")
            ->setVersion($version);

        $this->form->secrets['redis_host']['data'] = $this->service->getSlug();
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
            [null, 6379, 'tcp']
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'redis_host',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
