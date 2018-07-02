<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class MySQL extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mysql';

    /** @var Form\Service\MySQLCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\MySQLCreate();
    }

    public function create()
    {
        $version = (string) number_format($this->form->version, 1);

        $this->service->setName($this->form->name)
            ->setImage("mysql:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'MYSQL_ROOT_PASSWORD_FILE' => '/run/secrets/mysql_root_password',
                'MYSQL_DATABASE_FILE'      => '/run/secrets/mysql_database',
                'MYSQL_USER_FILE'          => '/run/secrets/mysql_user',
                'MYSQL_PASSWORD_FILE'      => '/run/secrets/mysql_password',
            ]);

        $this->form->secrets['mysql_host']['data'] = $this->service->getSlug();
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
            [null, 3306, 'tcp']
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'mysql_host',
            'mysql_root_password',
            'mysql_database',
            'mysql_user',
            'mysql_password',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [
                "my-cnf-{$this->service->getVersion()}",
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
