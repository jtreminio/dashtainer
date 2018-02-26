<?php

namespace DashtainerBundle\Docker\Handler;

use DashtainerBundle\Docker;

class Blackfire extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'blackfire';

    public function getDockerConfig() : array
    {
        return [
            'image'       => 'blackfire/blackfire',
            'environment' => $this->getEnvironment(),
            'networks'    => [
                $this->projectName,
                'web',
            ],
            'volumes'     => $this->getVolumes(),
        ];
    }

    public function writeFiles()
    {
    }

    protected function getVolumes() : array
    {
        $volumes = [];

        return $this->parseVolumes($volumes);
    }
}
