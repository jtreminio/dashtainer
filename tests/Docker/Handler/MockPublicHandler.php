<?php

namespace PodYardBundle\Tests\Docker\Handler;

use PodYardBundle\Docker;

class MockPublicHandler extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'mock-public';

    public function getDockerConfig() : array
    {
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
    }

    protected function getVolumes() : array
    {
        return [];
    }
}
