<?php

namespace PodYardBundle\Tests\Docker\Handler;

use PodYardBundle\Docker;
use PodYardBundle\Entity;

class MockPublicHandlerWithPrivateHandler extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'mock-public-with-private';

    public function getDockerConfig() : array
    {
        $extraServiceConfig = [
            'service_type' => 'mock-private',
            'name'         => 'extra-private-service',
        ];

        $extraService = new Entity\PodYardServiceConfig($extraServiceConfig);
        $extraService->setEnvironment([
            'IS_EXTRA' => 'yes!',

        ]);

        $this->addExtraServiceConfig($extraService);

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