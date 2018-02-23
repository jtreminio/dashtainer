<?php

namespace PodYardBundle\Docker\Handler;

use PodYardBundle\Docker;

class MailHog extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'mailhog';

    public function getDockerConfig() : array
    {
        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
            'traefik.port=8025',
        ];

        $labels = array_merge($labels, $this->pyServiceConfig->getLabels());

        return [
            'image'       => "mailhog/mailhog:{$this->getVersion()}",
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
    }

    protected function getVolumes() : array
    {
        $volumes = [];

        return $this->parseVolumes($volumes);
    }

    protected function getVersion() : string
    {
        return 'latest';
    }
}
