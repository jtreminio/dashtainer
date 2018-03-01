<?php

namespace Dashtainer\Tests\Docker\Handler;

use Dashtainer\Docker\HandlerInterface;
use Dashtainer\Docker\Handler\PostgreSQL;
use Dashtainer\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class PostgreSQLTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(PostgreSQL::class)
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
  postgresqlservice:
    image: 'postgres:9.6'
    restart: always
    environment:
      - POSTGRES_DB=db1
      - POSTGRES_USER=db1
      - POSTGRES_PASSWORD=db1
    networks:
      - unit-test-project
      - web
    labels:
      - traefik.backend=unit-test-project
      - traefik.docker.network=traefik_webgateway
      - 'traefik.frontend.rule=Host:postgresqlservice.localhost'
    volumes:
      - "$PWD/postgresqlservice/datadir:/var/lib/postgresql/data:delegated"
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
    postgresqlservice:
        service_type: postgresql
        name: postgresqlservice
        settings:
            modules:
                system: []
            version: 9.6
        environment:
            POSTGRES_DB: db1
            POSTGRES_USER: db1
            POSTGRES_PASSWORD: db1
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:postgresqlservice.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
