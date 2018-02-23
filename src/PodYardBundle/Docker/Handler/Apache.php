<?php

namespace PodYardBundle\Docker\Handler;

use PodYardBundle\Docker;
use PodYardBundle\Util;

class Apache extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'apache';

    public function getDockerConfig() : array
    {
        $settings = $this->pyServiceConfig->getSettings();

        $systemPackages  = array_unique($settings['modules']['system'] ?? []);
        $enabledModules  = array_unique($settings['modules']['apache_enabled'] ?? []);
        $disabledModules = array_unique($settings['modules']['apache_disabled'] ?? []);

        sort($systemPackages);
        sort($enabledModules);
        sort($disabledModules);

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
                    'SYSTEM_PACKAGES'        => implode(' ', $systemPackages),
                    'APACHE_MODULES_ENABLE'  => implode(' ', $enabledModules),
                    'APACHE_MODULES_DISABLE' => implode(' ', $disabledModules),
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

        $vhost = $this->templating->render(
            '@PodYard/docker/apache/vhost.twig',
            ['vhost' => $settings['vhost']]
        );

        file_put_contents(
            "{$this->targetDir}/vhost.conf",
            Util\Strings::removeExtraLineBreaks($vhost)
        );

        $conf = shell_exec(sprintf('python %s/apachelint.py %s',
            $this->binDir,
            "{$this->targetDir}/vhost.conf"
        ));

        file_put_contents(
            "{$this->targetDir}/vhost.conf",
            $conf
        );
    }

    protected function getVolumes() : array
    {
        $name = $this->pyServiceConfig->getName();

        $volumes = [
            [
                'source'        => "\$PWD/{$name}/vhost.conf",
                'target'        => '/etc/apache2/sites-enabled/000-default.conf',
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }
}
