<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class MongoDB extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'mongodb';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\MongoDBCreate();
    }

    /**
     * @param Form\Docker\Service\MongoDBCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("mongo:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData([$form->datastore])
            ->setService($service);

        $service->addMeta($dataStoreMeta);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:27017"] : [];

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData($portMetaData)
            ->setService($service);

        $service->addMeta($portMeta)
            ->setPorts($servicePort);

        $this->serviceRepo->save($dataStoreMeta, $versionMeta, $portMeta, $service);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setSource("\$PWD/{$service->getSlug()}/datadir")
            ->setTarget('/data/db')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
            ->setService($service);

        if ($form->datastore == 'local') {
            $serviceDatastoreVol->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $service->addVolume($serviceDatastoreVol);

            $this->serviceRepo->save($serviceDatastoreVol, $service);
        }

        if ($form->datastore !== 'local') {
            $projectDatastoreVol = new Entity\Docker\Volume();
            $projectDatastoreVol->setName("{$service->getSlug()}-datastore")
                ->setProject($service->getProject());

            $serviceDatastoreVol->setSource($projectDatastoreVol->getSlug())
                ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

            $projectDatastoreVol->addServiceVolume($serviceDatastoreVol);
            $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);
            $service->addVolume($serviceDatastoreVol);

            $this->serviceRepo->save(
                $projectDatastoreVol, $serviceDatastoreVol, $service
            );
        }

        $this->customFilesCreate($service, $form);

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

        return [
            'version'     => $version,
            'datastore'   => $datastore,
            'bindPort'    => $bindPort,
            'portConfirm' => $portConfirm,
        ];
    }

    /**
     * @param Entity\Docker\Service             $service
     * @param Form\Docker\Service\MongoDBCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:27017"] : [];

        $portMeta = $service->getMeta('bind-port');
        $portMeta->setData($portMetaData);

        $this->serviceRepo->save($dataStoreMeta, $portMeta);

        $service->setPorts($servicePort);

        $serviceDatastoreVol = $service->getVolume('datastore');
        $projectDatastoreVol = $serviceDatastoreVol->getProjectVolume();

        if ($form->datastore == 'local' && $projectDatastoreVol) {
            $projectDatastoreVol->removeServiceVolume($serviceDatastoreVol);
            $serviceDatastoreVol->setProjectVolume(null);

            $serviceDatastoreVol->setName('datastore')
                ->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $this->serviceRepo->save($serviceDatastoreVol);

            if ($projectDatastoreVol->getServiceVolumes()->isEmpty()) {
                $this->serviceRepo->delete($projectDatastoreVol);
            }
        }

        if ($form->datastore !== 'local') {
            if (!$projectDatastoreVol) {
                $projectDatastoreVol = new Entity\Docker\Volume();
                $projectDatastoreVol->setName("{$service->getSlug()}-datastore")
                    ->setProject($service->getProject());

                $projectDatastoreVol->addServiceVolume($serviceDatastoreVol);
                $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);
            }

            $serviceDatastoreVol->setSource($projectDatastoreVol->getSlug())
                ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

            $this->serviceRepo->save($projectDatastoreVol, $serviceDatastoreVol);
        }

        $this->customFilesUpdate($service, $form);

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

        for ($i = 27018; $i < 65535; $i++) {
            if (!in_array($i, $ports)) {
                return $i;
            }
        }

        return 27017;
    }
}
