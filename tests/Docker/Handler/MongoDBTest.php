<?php

namespace DashtainerBundle\Tests\Docker\Handler;

use DashtainerBundle\Docker\HandlerInterface;
use DashtainerBundle\Docker\Handler\MongoDB;
use DashtainerBundle\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class MongoDBTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(MongoDB::class)
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
  mongodbservice:
    image: 'mongo:3.6'
    restart: always
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - traefik.backend=unit-test-project
      - traefik.docker.network=traefik_webgateway
      - 'traefik.frontend.rule=Host:mongodbservice.localhost'
    volumes:
      - "$PWD/mongodbservice/datadir:/data/db:delegated"
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
    mongodbservice:
        service_type: mongodb
        name: mongodbservice
        settings:
            version: 3.6
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mongodbservice.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
