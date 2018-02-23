<?php

namespace PodYardBundle\Tests\Docker\Handler;

use PodYardBundle\Docker\HandlerInterface;
use PodYardBundle\Docker\Handler\Beanstalkd;
use PodYardBundle\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class BeanstalkdTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(Beanstalkd::class)
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
  beanstalkdservice:
    build:
      context: ./beanstalkdservice
      dockerfile: Dockerfile
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - 'traefik.frontend.rule=Host:beanstalkdservice.localhost'
    volumes:
      - "$PWD/beanstalkdservice/binlog:/var/lib/beanstalkd/binlog:delegated"
      - "~/www:/var/www:cached"

EOD;

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    protected function getConfig() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    beanstalkdservice:
        service_type: beanstalkd
        name: beanstalkdservice
        settings: {  }
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:beanstalkdservice.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
