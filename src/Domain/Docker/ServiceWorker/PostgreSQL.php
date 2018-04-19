<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class PostgreSQL extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'postgresql';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\PostgreSQLCreate();
    }

    /**
     * @param Form\Docker\Service\PostgreSQLCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("postgres:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $service->setEnvironments([
            'POSTGRES_DB'       => $form->postgres_db,
            'POSTGRES_USER'     => $form->postgres_user,
            'POSTGRES_PASSWORD' => $form->postgres_password,
        ]);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:5432"] : [];

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData($portMetaData)
            ->setService($service);

        $service->addMeta($portMeta)
            ->setPorts($servicePort);

        $this->serviceRepo->save($versionMeta, $portMeta, $service);

        $configFileConf = new Entity\Docker\ServiceVolume();
        $configFileConf->setName('postgresql.conf')
            ->setSource("\$PWD/{$service->getSlug()}/postgresql.conf")
            ->setTarget('/etc/postgresql/postgresql.conf')
            ->setData($form->file['postgresql.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configFileConf);

        $this->serviceRepo->save($configFileConf, $service);

        $this->createDatastore($service, $form, '/var/lib/postgresql/data');

        $this->userFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [
            'bindPort' => $this->getOpenBindPort($project),
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version   = $service->getMeta('version')->getData()[0];
        $version   = (string) number_format($version, 1);
        $datastore = $service->getMeta('datastore')->getData()[0];

        $bindPortMeta = $service->getMeta('bind-port');
        $bindPort     = $bindPortMeta->getData()[0]
            ?? $this->getOpenBindPort($service->getProject());
        $portConfirm  = $bindPortMeta->getData()[0] ?? false;

        $env = $service->getEnvironments();

        $postgres_db       = $env['POSTGRES_DB'];
        $postgres_user     = $env['POSTGRES_USER'];
        $postgres_password = $env['POSTGRES_PASSWORD'];

        $configFileConf = $service->getVolume('postgresql.conf');

        $userFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'version'           => $version,
            'datastore'         => $datastore,
            'bindPort'           => $bindPort,
            'portConfirm'        => $portConfirm,
            'postgres_db'       => $postgres_db,
            'postgres_user'     => $postgres_user,
            'postgres_password' => $postgres_password,
            'configFiles'       => [
                'postgresql.conf' => $configFileConf,
            ],
            'userFiles'         => $userFiles,
        ];
    }

    /**
     * @param Entity\Docker\Service                $service
     * @param Form\Docker\Service\PostgreSQLCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setEnvironments([
            'POSTGRES_DB'       => $form->postgres_db,
            'POSTGRES_USER'     => $form->postgres_user,
            'POSTGRES_PASSWORD' => $form->postgres_password,
        ]);

        $this->addToPrivateNetworks($service, $form);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:5432"] : [];

        $portMeta = $service->getMeta('bind-port');
        $portMeta->setData($portMetaData);

        $this->serviceRepo->save($portMeta);

        $service->setPorts($servicePort);

        $configFileConf = $service->getVolume('postgresql.conf');
        $configFileConf->setData($form->file['postgresql.conf'] ?? '');

        $this->serviceRepo->save($configFileConf);

        $this->updateDatastore($service, $form);

        $this->userFilesUpdate($service, $form);

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

        for ($i = 5433; $i < 65535; $i++) {
            if (!in_array($i, $ports)) {
                return $i;
            }
        }

        return 5432;
    }
}
