<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class MySQL extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mysql';

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\MySQLCreate();
    }

    /**
     * @param Form\Docker\Service\MySQLCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $version = (string) number_format($form->version, 1);

        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage("mysql:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'MYSQL_ROOT_PASSWORD_FILE' => '/run/secrets/mysql_root_password',
                'MYSQL_DATABASE_FILE'      => '/run/secrets/mysql_database',
                'MYSQL_USER_FILE'          => '/run/secrets/mysql_user',
                'MYSQL_PASSWORD_FILE'      => '/run/secrets/mysql_password',
            ]);

        $form->secrets['mysql_host']['data'] = $service->getSlug();

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'fileHighlight' => 'ini',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return array_merge(parent::getViewParams($service), [
            'fileHighlight' => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service           $service
     * @param Form\Docker\Service\MySQLCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();
    }

    protected function internalNetworksArray() : array
    {
        return [];
    }

    protected function internalPortsArray() : array
    {
        return [
            [null, 3306, 'tcp']
        ];
    }

    protected function internalSecretsArray() : array
    {
        return [
            'mysql_host',
            'mysql_root_password',
            'mysql_database',
            'mysql_user',
            'mysql_password',
        ];
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
                "my-cnf-{$this->version}",
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
