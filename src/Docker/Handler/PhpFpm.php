<?php

namespace Dashtainer\Docker\Handler;

use Dashtainer\Docker;
use Dashtainer\Entity;
use Dashtainer\Util\IniWriter;

class PhpFpm extends Docker\HandlerAbstract implements Docker\HandlerInterface
{
    protected const SERVICE_TYPE = 'php-fpm';

    public function getDockerConfig() : array
    {
        $settings = $this->pyServiceConfig->getSettings();

        $phpModules = $settings['modules']['php'] ?? [];

        if ($this->isToolEnabled('xdebug')) {
            $phpModules[] = 'xdebug';
        }

        $systemPackages = array_unique($settings['modules']['system'] ?? []);
        $phpModules     = array_unique($phpModules);
        $pearModules    = array_unique($settings['modules']['pear'] ?? []);
        $peclModules    = array_unique($settings['modules']['pecl'] ?? []);

        sort($systemPackages);
        sort($phpModules);
        sort($pearModules);
        sort($peclModules);

        if ($this->isToolEnabled('blackfire')) {
            $blackfireConfig = array_merge([
                'service_type' => 'blackfire',
                'name'         => "blackfire-{$this->pyServiceConfig->getName()}",
            ], $settings['tools']['blackfire']['settings'] ?? []);

            $this->pyServiceConfig->addEnvironment(
                'BLACKFIRE_HOST',
                $blackfireConfig['name']
            );

            $serverId    = $blackfireConfig['BLACKFIRE_SERVER_ID'] ?? 'REPLACE_ME';
            $serverToken = $blackfireConfig['BLACKFIRE_SERVER_TOKEN'] ?? 'REPLACE_ME';

            $blackfire = new Entity\DashtainerServiceConfig($blackfireConfig);
            $blackfire->setEnvironment([
                'BLACKFIRE_SERVER_ID'    => $serverId,
                'BLACKFIRE_SERVER_TOKEN' => $serverToken,
            ]);

            $this->addExtraServiceConfig($blackfire);
        }

        return [
            'build'       => [
                'context'    => "./{$this->pyServiceConfig->getName()}",
                'dockerfile' => 'Dockerfile',
                'args'       => [
                    'SYSTEM_PACKAGES'   => implode(' ', $systemPackages),
                    'PHP_MODULES'       => implode(' ', $phpModules),
                    'PEAR_MODULES'      => implode(' ', $pearModules),
                    'PECL_MODULES'      => implode(' ', $peclModules),
                    'INSTALL_COMPOSER'  => $this->isToolEnabled('composer') ? 1 : 0,
                    'INSTALL_BLACKFIRE' => $this->isToolEnabled('blackfire') ? 1 : 0,
                ],
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

        $conf = $this->templating->render(
            '@Dashtainer/docker/php-fpm/Dockerfile.twig',
            ['version' => $this->getVersion()]
        );

        file_put_contents(
            "{$this->targetDir}/Dockerfile",
            $conf
        );

        $conf = $this->templating->render(
            '@Dashtainer/docker/php-fpm/fpm_conf.twig',
            ['version' => $this->getVersion()]
        );

        file_put_contents(
            "{$this->targetDir}/fpm.conf",
            $conf
        );

        $settings = $this->pyServiceConfig->getSettings();

        $phpIni = array_merge(
            ['custom' => $settings['ini'] ?? []],
            $this->getToolsIni()
        );

        $fpmPoolIni = ['www' => $settings['fpm_pool_ini'] ?? []];

        IniWriter::writeData(
            "{$this->targetDir}/php.ini",
            $phpIni
        );

        IniWriter::writeData(
            "{$this->targetDir}/fpm_pool.conf",
            $fpmPoolIni
        );
    }

    protected function getToolsIni() : array
    {
        $settings = $this->pyServiceConfig->getSettings();
        $tools    = $settings['tools'] ?? [];

        $ini = [];
        foreach ($tools as $name => $toolSettings) {
            if (empty($toolSettings['install'])) {
                continue;
            }

            if (empty($toolSettings['ini'])) {
                continue;
            }

            $ini[$name] = $toolSettings['ini'];
        }

        return $ini;
    }

    protected function getVolumes() : array
    {
        $name = $this->pyServiceConfig->getName();

        $volumes = [
            [
                'source'        => "\$PWD/{$name}/php.ini",
                'target'        => "/etc/php/{$this->getVersion()}/cli/conf.d/zzzz_custom.ini",
                'configuration' => 'delegated',
            ],
            [
                'source'        => "\$PWD/{$name}/php.ini",
                'target'        => "/etc/php/{$this->getVersion()}/fpm/conf.d/zzzz_custom.ini",
                'configuration' => 'delegated',
            ],
            [
                'source'        => "\$PWD/{$name}/fpm.conf",
                'target'        => "/etc/php/{$this->getVersion()}/fpm/php-fpm.conf",
                'configuration' => 'delegated',
            ],
            [
                'source'        => "\$PWD/{$name}/fpm_pool.conf",
                'target'        => "/etc/php/{$this->getVersion()}/fpm/pool.d/www.conf",
                'configuration' => 'delegated',
            ],
        ];

        return $this->parseVolumes($volumes);
    }

    protected function getVersion() : string
    {
        $version = $this->pyServiceConfig->getSettings()['version'] ?? 7.2;

        return (string) number_format($version, 1);
    }
}
