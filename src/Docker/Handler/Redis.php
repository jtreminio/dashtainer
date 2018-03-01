<?php

namespace Dashtainer\Docker\Handler;

use Dashtainer\Docker;

class Redis extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'redis';

    public function getDockerConfig() : array
    {
        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
        ];

        $labels = array_merge($labels, $this->pyServiceConfig->getLabels());

        return [
            'image'       => "redis:{$this->getVersion()}",
            'networks'    => [
                $this->projectName,
                'web',
            ],
            'environment' => $this->getEnvironment(),
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
                'target'        => '/data',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }

    protected function getVersion() : string
    {
        $version = $this->pyServiceConfig->getSettings()['version'] ?? 4.0;

        return (string) number_format($version, 1);
    }
}
