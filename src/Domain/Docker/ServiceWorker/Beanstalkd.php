<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Beanstalkd extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'beanstalkd';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\BeanstalkdCreate();
    }

    /**
     * @param Form\Docker\Service\BeanstalkdCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('Dockerfile');

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData([$form->datastore])
            ->setService($service);

        $service->addMeta($dataStoreMeta);

        $this->serviceRepo->save($dataStoreMeta, $service);

        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $service->addVolume($dockerfile);

        $this->serviceRepo->save($dockerfile, $service);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setSource("\$PWD/{$service->getSlug()}/datadir")
            ->setTarget('/var/lib/beanstalkd/binlog')
            ->setConsistency(null)
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

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $datastore = $service->getMeta('datastore')->getData()[0];

        $dockerfile  = $service->getVolume('Dockerfile');

        return [
            'datastore'   => $datastore,
            'configFiles' => [
                'Dockerfile' => $dockerfile,
            ],
        ];
    }

    /**
     * @param Entity\Docker\Service                $service
     * @param Form\Docker\Service\BeanstalkdCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $this->serviceRepo->save($dataStoreMeta);

        $dockerfile = $service->getVolume('Dockerfile');
        $dockerfile->setData($form->file['Dockerfile'] ?? '');

        $this->serviceRepo->save($dockerfile);

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

        return $service;
    }
}
