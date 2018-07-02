<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class PostgreSQL extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'postgresql';

    /** @var Form\Service\PostgreSQLCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\PostgreSQLCreate();
    }

    public function create()
    {
        $version = (string) number_format($this->form->version, 1);

        $this->service->setName($this->form->name)
            ->setImage("postgres:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'POSTGRES_DB_FILE'       => '/run/secrets/postgres_db',
                'POSTGRES_USER_FILE'     => '/run/secrets/postgres_user',
                'POSTGRES_PASSWORD_FILE' => '/run/secrets/postgres_password',
            ]);

        $this->form->secrets['postgres_host']['data'] = $this->service->getSlug();
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
            [null, 5432, 'tcp']
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'postgres_host',
            'postgres_db',
            'postgres_user',
            'postgres_password',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [
                "conf-{$this->service->getVersion()}",
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
