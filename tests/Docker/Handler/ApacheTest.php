<?php

namespace Dashtainer\Tests\Docker\Handler;

use Dashtainer\Docker\HandlerInterface;
use Dashtainer\Docker\Handler\Apache;
use Dashtainer\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class ApacheTest extends KernelTestCase
{
    /** @var HandlerInterface */
    protected $handler;

    /** @var Manager */
    protected $manager;

    protected function setUp()
    {
        $binDir    = __DIR__;
        $sourceDir = __DIR__ . '/../../../docker-source';

        /** @var Templating\EngineInterface|MockBuilder $templating */
        $templating = $this->getMockBuilder(Templating\EngineInterface::class)
            ->getMock();

        $handler = $this->getMockBuilder(Apache::class)
            ->setConstructorArgs([$templating, $binDir, $sourceDir])
            ->setMethods(['writeFiles'])
            ->getMock();

        $this->manager = new Manager();
        $this->manager->setHandlers([$handler]);
    }

    public function testHandler()
    {
        $config = Yaml::parse($this->getConfig());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  apacheservice:
    build:
      context: ./apacheservice
      dockerfile: Dockerfile
      args:
        SYSTEM_PACKAGES: vim
        APACHE_MODULES_ENABLE: 'http2 mpm_event proxy_fcgi rewrite'
        APACHE_MODULES_DISABLE: 'mpm_prefork mpm_worker'
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - traefik.backend=unit-test-project
      - traefik.docker.network=traefik_webgateway
      - 'traefik.frontend.rule=Host:vhost1.localhost'
    volumes:
      - "$PWD/apacheservice/vhost.conf:/etc/apache2/sites-enabled/000-default.conf:delegated"
      - "~/www:/var/www:cached"

EOD;

        $this->manager->setDashtainerConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    protected function getConfig() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    apacheservice:
        service_type: apache
        name: apacheservice
        settings:
            modules:
                system:
                    - vim
                apache_enabled:
                    - http2
                    - mpm_event
                    - proxy_fcgi
                    - rewrite
                apache_disabled:
                    - mpm_prefork
                    - mpm_worker
            vhost:
                server_name: vhost1.localhost
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:vhost1.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
