<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Elasticsearch extends WorkerAbstract implements WorkerInterface
{
    public function getServiceType() : Entity\Docker\ServiceType
    {
        if (!$this->serviceType) {
            $this->serviceType = $this->serviceTypeRepo->findBySlug('elasticsearch');
        }

        return $this->serviceType;
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
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
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

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

        $this->serviceRepo->save($service);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'fileHighlight' => 'yml',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version   = (string) $service->getMeta('version')->getData()[0];
        $heap_size = $service->getMeta('heap_size')->getData()[0];

        return array_merge(parent::getViewParams($service), [
            'version'       => $version,
            'heap_size'     => $heap_size,
            'fileHighlight' => 'yml',
        ]);
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

        $heapsizeMeta = $service->getMeta('heap_size');
        $heapsizeMeta->setData([$form->heap_size]);

        $this->serviceRepo->save($heapsizeMeta);

        $this->addToPrivateNetworks($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

        return $service;
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
                'elasticsearch-yml',
            ],
            'other' => [
                'datadir',
            ],
        ];
    }

    protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array {
        return [];
    }
}
