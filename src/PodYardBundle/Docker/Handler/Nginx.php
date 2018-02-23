<?php

namespace PodYardBundle\Docker\Handler;

use PodYardBundle\Docker;
use PodYardBundle\Util;

class Nginx extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'nginx';

    public function getDockerConfig() : array
    {
        $settings = $this->pyServiceConfig->getSettings();

        $systemPackages  = array_unique($settings['modules']['system'] ?? []);

        sort($systemPackages);

        $labels = [
            "traefik.backend={$this->projectName}",
            'traefik.docker.network=traefik_webgateway',
        ];

        $labels = array_merge($labels, $this->pyServiceConfig->getLabels());

        return [
            'build'       => [
                'context'    => "./{$this->pyServiceConfig->getName()}",
                'dockerfile' => 'Dockerfile',
                'args'       => [
                    'SYSTEM_PACKAGES' => implode(' ', $systemPackages),
                ],
            ],
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
        exec(sprintf('cp -r %s %s',
            $this->sourceDir,
            $this->targetDir
        ));

        $settings = $this->pyServiceConfig->getSettings();

        $this->generateFile(
            '@PodYard/docker/nginx/server.twig',
            "{$this->targetDir}/server.conf",
            ['server' => $settings['server']]
        );

        $this->generateFile(
            '@PodYard/docker/nginx/confd_core.twig',
            "{$this->targetDir}/confd_core.conf",
            ['conf' => $settings['core'] ?? []]
        );

        $this->generateFile(
            '@PodYard/docker/nginx/confd_proxy.twig',
            "{$this->targetDir}/confd_proxy.conf",
            ['conf' => $settings['proxy'] ?? []]
        );
    }

    protected function getVolumes() : array
    {
        $name = $this->pyServiceConfig->getName();

        $volumes = [
            [
                'source'        => "\$PWD/{$name}/confd_core.conf",
                'target'        => '/etc/nginx/conf.d/core.conf',
                'configuration' => 'delegated',
            ],
            [
                'source'        => "\$PWD/{$name}/confd_proxy.conf",
                'target'        => '/etc/nginx/conf.d/proxy.conf',
                'configuration' => 'delegated',
            ],
            [
                'source'        => "\$PWD/{$name}/server.conf",
                'target'        => '/etc/nginx/sites-available/default',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }

    protected function generateFile(
        string $sourceTemplate,
        string $targetFile,
        array $parameters
    ) {
        $rendered = $this->templating->render(
            $sourceTemplate,
            $parameters
        );

        file_put_contents(
            $targetFile,
            Util\Strings::removeExtraLineBreaks($rendered)
        );

        exec(sprintf('python3 %s/nginxfmt.py %s',
            $this->binDir,
            $targetFile
        ));
    }
}
