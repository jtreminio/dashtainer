<?php

namespace Dashtainer\Docker\Handler;

use Dashtainer\Docker;

class MySQL extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'mysql';

    public function getDockerConfig() : array
    {
        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
        ];

        $labels = array_merge($labels, $this->pyServiceConfig->getLabels());

        return [
            'image'       => "mysql:{$this->getVersion()}",
            'restart'     => 'always',
            'environment' => $this->getEnvironment(),
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
                'target'        => '/var/lib/mysql',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }

    protected function getVersion() : string
    {
        $version = $this->pyServiceConfig->getSettings()['version'] ?? 5.7;

        return (string) number_format($version, 1);
    }
}
