<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class Elasticsearch extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'elasticsearch';

    /** @var Form\Service\ElasticsearchCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\ElasticsearchCreate();
    }

    public function create()
    {
        $version = (string) $this->form->version;

        $this->service->setName($this->form->name)
            ->setImage("docker.elastic.co/elasticsearch/elasticsearch-oss:{$version}")
            ->setVersion($version)
            ->setRestart(Entity\Service::RESTART_ALWAYS)
            ->setEnvironments([
                'ES_JAVA_OPTS' => "-Xms{$this->form->heap_size} -Xmx{$this->form->heap_size}",
            ]);

        $ulimits = $this->service->getUlimits();
        $ulimits->setMemlock(-1, -1);
        $this->service->setUlimits($ulimits);

        $heapsizeMeta = new Entity\ServiceMeta();
        $heapsizeMeta->setName('heap_size')
            ->setData([$this->form->heap_size])
            ->setService($this->service);

        $this->form->secrets['elasticsearch_host']['data'] = $this->service->getSlug();
    }

    public function update()
    {
        $this->service->setEnvironments([
            'ES_JAVA_OPTS' => "-Xms{$this->form->heap_size} -Xmx{$this->form->heap_size}",
        ]);

        $heapsizeMeta = $this->service->getMeta('heap_size');
        $heapsizeMeta->setData([$this->form->heap_size]);
    }

    public function getCreateParams() : array
    {
        return [
            'fileHighlight' => 'yml',
        ];
    }

    public function getViewParams() : array
    {
        $heap_size = $this->service->getMeta('heap_size')->getData()[0];

        return [
            'heap_size'     => $heap_size,
            'fileHighlight' => 'ini',
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'elasticsearch_host',
        ];
    }

    public function getInternalVolumes() : array
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
