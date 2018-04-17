<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

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

        $this->serviceRepo->save($versionMeta, $heapsizeMeta, $service);

        $configYml = new Entity\Docker\ServiceVolume();
        $configYml->setName('elasticsearch.yml')
            ->setSource("\$PWD/{$service->getSlug()}/elasticsearch.yml")
            ->setTarget('/usr/share/elasticsearch/config/elasticsearch.yml')
            ->setData($form->file['elasticsearch.yml'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configYml);

        $this->serviceRepo->save($configYml, $service);

        $this->createDatastore($service, $form, '/usr/share/elasticsearch/data');

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

        $heapsizeMeta = $service->getMeta('heap_size');
        $heapsizeMeta->setData([$form->heap_size]);

        $this->serviceRepo->save($heapsizeMeta);

        $configYml = $service->getVolume('elasticsearch.yml');
        $configYml->setData($form->file['elasticsearch.yml'] ?? '');

        $this->serviceRepo->save($configYml);

        $this->updateDatastore($service, $form);

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
