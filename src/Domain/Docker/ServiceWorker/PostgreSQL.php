<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class PostgreSQL extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'postgresql';

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\PostgreSQLCreate();
    }

    /**
     * @param Form\Docker\Service\PostgreSQLCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $version = (string) number_format($form->version, 1);

        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage("postgres:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'POSTGRES_DB_FILE'       => '/run/secrets/postgres_db',
                'POSTGRES_USER_FILE'     => '/run/secrets/postgres_user',
                'POSTGRES_PASSWORD_FILE' => '/run/secrets/postgres_password',
            ]);

        $form->secrets['postgres_host']['data'] = $service->getSlug();

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
     * @param Entity\Docker\Service                $service
     * @param Form\Docker\Service\PostgreSQLCreate $form
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
            [null, 5432, 'tcp']
        ];
    }

    protected function internalSecretsArray() : array
    {
        return [
            'postgres_host',
            'postgres_db',
            'postgres_user',
            'postgres_password',
        ];
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
                "conf-{$this->version}",
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
