<?php

namespace DashtainerBundle\Tests\Docker\Handler;

use DashtainerBundle\Docker\HandlerInterface;
use DashtainerBundle\Docker\Handler\MailHog;
use DashtainerBundle\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class MailHogTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(MailHog::class)
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
  mailhogservice:
    image: 'mailhog/mailhog:latest'
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - traefik.backend=unit-test-project
      - traefik.docker.network=traefik_webgateway
      - traefik.port=8025
      - 'traefik.frontend.rule=Host:mailhogservice.localhost'
    volumes:
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
    mailhogservice:
        service_type: mailhog
        name: mailhogservice
        settings:
            version: latest
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mailhogservice.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
