<?php

namespace PodYardBundle\Tests\Docker;

use PodYardBundle\Docker\HandlerInterface;
use PodYardBundle\Docker\Manager;
use PodYardBundle\Tests\Docker\Handler\MockPrivateHandler;
use PodYardBundle\Tests\Docker\Handler\MockPublicHandler;
use PodYardBundle\Tests\Docker\Handler\MockPublicHandlerWithPrivateHandler;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class ManagerTest extends KernelTestCase
{
    /** @var Manager */
    protected $manager;

    protected function setUp()
    {
        $this->manager = new Manager();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Empty PodYard config file
     */
    public function testGetDockerConfigYamlThrowsExceptionOnNoPodYardConfigFilePassed()
    {
        $this->manager->generateArchive(__DIR__);
    }

    public function testGetDockerConfigYamlOnNoHandlers()
    {
        $config = Yaml::parse($this->getConfigWithPublicServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services: {  }

EOD;

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlIgnoresNonDefinedServiceType()
    {
        $config = Yaml::parse($this->getConfigWithFakeServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services: {  }

EOD;

        $this->manager->setHandlers([$this->getPublicHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlIgnoresPrivateServiceType()
    {
        $config = Yaml::parse($this->getConfigWithPrivateServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services: {  }

EOD;

        $this->manager->setPrivateHandlers([$this->getPrivateHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlParsesPublicServiceType()
    {
        $config = Yaml::parse($this->getConfigWithPublicServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  mockServiceA:
    build:
      context: ./mockServiceA
      dockerfile: Dockerfile
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - 'traefik.frontend.rule=Host:mockServiceA.localhost'
    volumes: {  }

EOD;

        $this->manager->setHandlers([$this->getPublicHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlParsesPublicServiceTypeAndIgnoresUserDefinedPrivateServiceType()
    {
        $config = Yaml::parse($this->getConfigWithPublicAndPrivateServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  mockServiceA:
    build:
      context: ./mockServiceA
      dockerfile: Dockerfile
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - 'traefik.frontend.rule=Host:mockServiceA.localhost'
    volumes: {  }

EOD;

        $this->manager->setHandlers([$this->getPublicHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlIgnoresCustomUserData()
    {
        $config = Yaml::parse($this->getConfigWithPublicServiceTypeAndTrashData());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  mockServiceA:
    build:
      context: ./mockServiceA
      dockerfile: Dockerfile
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - 'traefik.frontend.rule=Host:mockServiceA.localhost'
    volumes: {  }

EOD;

        $this->manager->setHandlers([$this->getPublicHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testGetDockerConfigYamlParsesPublicServiceTypeThatCallsPrivateServiceType()
    {
        $config = Yaml::parse($this->getConfigWithPublicWithPrivateServiceType());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  mockServiceA:
    build:
      context: ./mockServiceA
      dockerfile: Dockerfile
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels:
      - 'traefik.frontend.rule=Host:mockServiceA.localhost'
    volumes: {  }
  extra-private-service:
    build:
      context: ./extra-private-service
      dockerfile: Dockerfile
    environment:
      - IS_EXTRA=yes!
    networks:
      - unit-test-project
      - web
    labels: {  }
    volumes: {  }

EOD;

        $this->manager->setHandlers([$this->getPublicWithPrivateHandler()]);
        $this->manager->setPrivateHandlers([$this->getPrivateHandler()]);

        $this->manager->setPodYardConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    protected function getPublicHandler() : HandlerInterface
    {
        $binDir    = __DIR__;
        $sourceDir = __DIR__;
        /** @var Templating\EngineInterface|MockBuilder $templating */
        $templating = $this->getMockBuilder(Templating\EngineInterface::class)
            ->getMock();

        return new MockPublicHandler($templating, $binDir, $sourceDir);
    }

    protected function getPublicWithPrivateHandler() : HandlerInterface
    {
        $binDir    = __DIR__;
        $sourceDir = __DIR__;
        /** @var Templating\EngineInterface|MockBuilder $templating */
        $templating = $this->getMockBuilder(Templating\EngineInterface::class)
            ->getMock();

        return new MockPublicHandlerWithPrivateHandler($templating, $binDir, $sourceDir);
    }

    protected function getPrivateHandler() : HandlerInterface
    {
        $binDir    = __DIR__;
        $sourceDir = __DIR__;
        /** @var Templating\EngineInterface|MockBuilder $templating */
        $templating = $this->getMockBuilder(Templating\EngineInterface::class)
            ->getMock();

        return new MockPrivateHandler($templating, $binDir, $sourceDir);
    }

    protected function getConfigWithFakeServiceType() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockServiceA:
        service_type: fake-service-type
        name: mockServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached
EOD;
    }

    protected function getConfigWithPrivateServiceType() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockPrivateServiceA:
        service_type: mock-private
        name: mockPrivateServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockPrivateServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }

    protected function getConfigWithPublicServiceType() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockServiceA:
        service_type: mock-public
        name: mockServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }

    protected function getConfigWithPublicServiceTypeAndTrashData() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockServiceA:
        service_type: mock-public
        name: mockServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

trashData:
    this:
        should:
            be:
    ignored: true
EOD;
    }

    protected function getConfigWithPublicAndPrivateServiceType() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockServiceA:
        service_type: mock-public
        name: mockServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached
    mockPrivateServiceA:
        service_type: mock-private
        name: mockPrivateServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockPrivateServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }

    protected function getConfigWithPublicWithPrivateServiceType() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    mockServiceA:
        service_type: mock-public-with-private
        name: mockServiceA
        settings:
            version: 4.0
        networks:
            - web
        labels:
            - "traefik.frontend.rule=Host:mockServiceA.localhost"
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
