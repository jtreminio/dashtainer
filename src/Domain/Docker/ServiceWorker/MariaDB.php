<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class MariaDB extends WorkerAbstract
{
    public function getServiceType() : Entity\Docker\ServiceType
    {
        if (!$this->serviceType) {
            $this->serviceType = $this->serviceTypeRepo->findBySlug('mariadb');
        }

        return $this->serviceType;
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\MariaDBCreate();
    }

    /**
     * @param Form\Docker\Service\MariaDBCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("mariadb:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD_FILE' => '/run/secrets/mysql_root_password',
            'MYSQL_DATABASE_FILE'      => '/run/secrets/mysql_database',
            'MYSQL_USER_FILE'          => '/run/secrets/mysql_user',
            'MYSQL_PASSWORD_FILE'      => '/run/secrets/mysql_password',
        ]);

        $this->serviceRepo->save($service);

        $form->secrets['mysql_host']['data'] = $service->getSlug();

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($versionMeta, $service);

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
        $version = $service->getMeta('version')->getData()[0];
        $version = (string) number_format($version, 1);

        return array_merge(parent::getViewParams($service), [
            'version'       => $version,
            'fileHighlight' => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service             $service
     * @param Form\Docker\Service\MariaDBCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

        return $service;
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
                'my-cnf',
            ],
            'other' => [
                'datadir',
            ],
        ];
    }
}
