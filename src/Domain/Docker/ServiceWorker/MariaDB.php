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
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:3306"] : [];

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData($portMetaData)
            ->setService($service);

        $service->addMeta($portMeta)
            ->setPorts($servicePort);

        $this->serviceRepo->save($versionMeta, $portMeta, $service);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'bindPort'      => $this->getOpenBindPort($project),
            'fileHighlight' => 'ini',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version = $service->getMeta('version')->getData()[0];
        $version = (string) number_format($version, 1);

        $bindPortMeta = $service->getMeta('bind-port');
        $bindPort     = $bindPortMeta->getData()[0]
            ?? $this->getOpenBindPort($service->getProject());
        $portConfirm  = $bindPortMeta->getData()[0] ?? false;

        return array_merge(parent::getViewParams($service), [
            'version'       => $version,
            'bindPort'      => $bindPort,
            'portConfirm'   => $portConfirm,
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
        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:3306"] : [];

        $portMeta = $service->getMeta('bind-port');
        $portMeta->setData($portMetaData);

        $this->serviceRepo->save($portMeta);

        $service->setPorts($servicePort);

        $this->updateNetworks($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

        return $service;
    }

    protected function getOpenBindPort(Entity\Docker\Project $project) : int
    {
        $bindPortMetas = $this->serviceRepo->getProjectBindPorts($project);

        $ports = [];
        foreach ($bindPortMetas as $meta) {
            if (!$data = $meta->getData()) {
                continue;
            }

            $ports []= $data[0];
        }

        for ($i = 3307; $i < 65535; $i++) {
            if (!in_array($i, $ports)) {
                return $i;
            }
        }

        return 3306;
    }

    protected function internalNetworksArray() : array
    {
        return [];
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
