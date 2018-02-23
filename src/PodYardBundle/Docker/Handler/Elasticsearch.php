<?php

namespace PodYardBundle\Docker\Handler;

use PodYardBundle\Docker;

class Elasticsearch extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'elasticsearch';

    public function getDockerConfig() : array
    {
        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
        ];

        $labels = array_merge($labels, $this->pyServiceConfig->getLabels());

        return [
            'image'       => "docker.elastic.co/elasticsearch/elasticsearch-oss:{$this->getVersion()}",
            'environment' => $this->getEnvironment(),
            'ulimits'     => [
                'memlock' => [
                    'soft' => -1,
                    'hard' => -1,
                ],
            ],
            'networks'    => [
                $this->projectName,
                'web',
            ],
            'labels'      => $labels,
            'volumes'     => $this->getVolumes(),
        ];
    }

    public function writeFiles()
    {
        mkdir($this->targetDir, 0775);
    }

    protected function getVolumes() : array
    {
        $name = $this->pyServiceConfig->getName();

        $volumes = [
            [
                'source'        => "\$PWD/{$name}/datadir",
                'target'        => '/usr/share/elasticsearch/data',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }

    protected function getVersion() : string
    {
        $version = $this->pyServiceConfig->getSettings()['version'] ?? '6.2.1';

        return (string) $version;
    }
}
