<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Elasticsearch extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'elasticsearch';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\ElasticsearchCreate();
    }

    /**
     * @param Form\Docker\Service\ElasticsearchCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) $form->version;

        $service->setImage("docker.elastic.co/elasticsearch/elasticsearch-oss:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $service->setEnvironments([
            'ES_JAVA_OPTS' => "-Xms{$form->heap_size} -Xmx{$form->heap_size}",
        ]);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $ulimits = $service->getUlimits();
        $ulimits->setMemlock(-1, -1);
        $service->setUlimits($ulimits);

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

        $heapsizeMeta = new Entity\Docker\ServiceMeta();
        $heapsizeMeta->setName('heap_size')
            ->setData([$form->heap_size])
            ->setService($service);

        $service->addMeta($heapsizeMeta);

        $this->serviceRepo->save($dataStoreMeta, $versionMeta, $heapsizeMeta, $service);

        $configYml = new Entity\Docker\ServiceVolume();
        $configYml->setName('elasticsearch.yml')
            ->setSource("\$PWD/{$service->getSlug()}/elasticsearch.yml")
            ->setTarget('/usr/share/elasticsearch/config/elasticsearch.yml')
            ->setData($form->file['elasticsearch.yml'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configYml);

        $this->serviceRepo->save($configYml, $service);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setSource("\$PWD/{$service->getSlug()}/datadir")
            ->setTarget('/usr/share/elasticsearch/data')
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
        $version   = (string) $service->getMeta('version')->getData()[0];
        $datastore = $service->getMeta('datastore')->getData()[0];
        $heap_size = $service->getMeta('heap_size')->getData()[0];

        $configYml = $service->getVolume('elasticsearch.yml');

        return [
            'version'             => $version,
            'datastore'           => $datastore,
            'heap_size'           => $heap_size,
            'configFiles'         => [
                'elasticsearch.yml' => $configYml,
            ],
        ];
    }

    /**
     * @param Entity\Docker\Service                   $service
     * @param Form\Docker\Service\ElasticsearchCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setEnvironments([
            'ES_JAVA_OPTS' => "-Xms{$form->heap_size} -Xmx{$form->heap_size}",
        ]);

        $this->addToPrivateNetworks($service, $form);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $heapsizeMeta = $service->getMeta('heap_size');
        $heapsizeMeta->setData([$form->heap_size]);

        $this->serviceRepo->save($dataStoreMeta, $heapsizeMeta);

        $configYml = $service->getVolume('elasticsearch.yml');
        $configYml->setData($form->file['elasticsearch.yml'] ?? '');

        $this->serviceRepo->save($configYml);

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
}
