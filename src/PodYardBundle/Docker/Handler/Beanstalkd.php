<?php

namespace PodYardBundle\Docker\Handler;

use PodYardBundle\Docker;

class Beanstalkd extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'beanstalkd';

    public function getDockerConfig() : array
    {
        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
        ];

        return [
            'build'       => [
                'context'    => "./{$this->pyServiceConfig->getName()}",
                'dockerfile' => 'Dockerfile',
            ],
            'environment' => $this->getEnvironment(),
            'networks'    => [
                $this->projectName,
                'web',
            ],
            'labels'      => $this->pyServiceConfig->getLabels(),
            'volumes'     => $this->getVolumes(),
        ];
    }

    public function writeFiles()
    {
        exec(sprintf('cp -r %s %s',
            $this->sourceDir,
            $this->targetDir
        ));
    }

    protected function getVolumes() : array
    {
        $name = $this->pyServiceConfig->getName();

        $volumes = [
            [
                'source'        => "\$PWD/{$name}/binlog",
                'target'        => '/var/lib/beanstalkd/binlog',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }
}
