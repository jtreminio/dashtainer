<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Elasticsearch extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'elasticsearch';

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
        $version = (string) $form->version;

        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project)
            ->setImage("docker.elastic.co/elasticsearch/elasticsearch-oss:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'ES_JAVA_OPTS' => "-Xms{$form->heap_size} -Xmx{$form->heap_size}",
            ]);

        $ulimits = $service->getUlimits();
        $ulimits->setMemlock(-1, -1);
        $service->setUlimits($ulimits);

        $heapsizeMeta = new Entity\Docker\ServiceMeta();
        $heapsizeMeta->setName('heap_size')
            ->setData([$form->heap_size])
            ->setService($service);

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $this->serviceRepo->persist($service, $heapsizeMeta);
        $this->serviceRepo->flush();

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
        $heap_size = $service->getMeta('heap_size')->getData()[0];

        return array_merge(parent::getViewParams($service), [
            'heap_size'     => $heap_size,
            'fileHighlight' => 'yml',
        ]);
    }

    /**
     * @param Entity\Docker\Service                   $service
     * @param Form\Docker\Service\ElasticsearchCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
        $service->setEnvironments([
            'ES_JAVA_OPTS' => "-Xms{$form->heap_size} -Xmx{$form->heap_size}",
        ]);

        $heapsizeMeta = $service->getMeta('heap_size');
        $heapsizeMeta->setData([$form->heap_size]);

        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->persist($service, $heapsizeMeta);
        $this->serviceRepo->flush();
    }

    protected function internalNetworksArray() : array
    {
        return [];
    }

    protected function internalPortsArray() : array
    {
        return [];
    }

    protected function internalSecretsArray() : array
    {
        return [];
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
}
