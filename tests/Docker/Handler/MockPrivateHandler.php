<?php

namespace Dashtainer\Tests\Docker\Handler;

use Dashtainer\Docker;

class MockPrivateHandler extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'mock-private';

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
