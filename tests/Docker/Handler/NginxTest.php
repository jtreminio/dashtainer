<?php

namespace DashtainerBundle\Tests\Docker\Handler;

use DashtainerBundle\Docker\HandlerInterface;
use DashtainerBundle\Docker\Handler\Nginx;
use DashtainerBundle\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class NginxTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(Nginx::class)
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
  nginxservice:
    build:
      context: ./nginxservice
      dockerfile: Dockerfile
      args:
        SYSTEM_PACKAGES: ''
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - traefik.backend=unit-test-project
      - traefik.docker.network=traefik_webgateway
      - 'traefik.frontend.rule=Host:vhost1.localhost'
    volumes:
      - "$PWD/nginxservice/confd_core.conf:/etc/nginx/conf.d/core.conf:delegated"
      - "$PWD/nginxservice/confd_proxy.conf:/etc/nginx/conf.d/proxy.conf:delegated"
      - "$PWD/nginxservice/server.conf:/etc/nginx/sites-available/default:delegated"
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
    nginxservice:
        service_type: nginx
        name: nginxservice
        settings:
            modules:
                system: []
            core:
                client_max_body_size: 10m
            proxy:
                proxy_redirect: off
            server:
                rewrite_www_to_non_www: 1
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
