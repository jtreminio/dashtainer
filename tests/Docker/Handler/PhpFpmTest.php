<?php

namespace Dashtainer\Tests\Docker\Handler;

use Dashtainer\Docker\HandlerInterface;
use Dashtainer\Docker\Handler\PhpFpm;
use Dashtainer\Docker\Handler\Blackfire;
use Dashtainer\Docker\Manager;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Templating;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class PhpFpmTest extends KernelTestCase
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

        $handler = $this->getMockBuilder(PhpFpm::class)
            ->setConstructorArgs([$templating, $binDir, $sourceDir])
            ->setMethods(['writeFiles'])
            ->getMock();

        $privateHandler = $this->getMockBuilder(Blackfire::class)
            ->setConstructorArgs([$templating, $binDir, $sourceDir])
            ->setMethods(['writeFiles'])
            ->getMock();

        $this->manager = new Manager();
        $this->manager->setHandlers([$handler]);
        $this->manager->setPrivateHandlers([$privateHandler]);
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
  phpfpmservice:
    build:
      context: ./phpfpmservice
      dockerfile: Dockerfile
      args:
        SYSTEM_PACKAGES: ''
        PHP_MODULES: 'intl mysql xdebug xml'
        PEAR_MODULES: ''
        PECL_MODULES: ''
        INSTALL_COMPOSER: 1
        INSTALL_BLACKFIRE: 0
    environment: {  }
    networks:
      - unit-test-project
      - web
    labels: {  }
    volumes:
      - "$PWD/phpfpmservice/php.ini:/etc/php/7.0/cli/conf.d/zzzz_custom.ini:delegated"
      - "$PWD/phpfpmservice/php.ini:/etc/php/7.0/fpm/conf.d/zzzz_custom.ini:delegated"
      - "$PWD/phpfpmservice/fpm.conf:/etc/php/7.0/fpm/php-fpm.conf:delegated"
      - "$PWD/phpfpmservice/fpm_pool.conf:/etc/php/7.0/fpm/pool.d/www.conf:delegated"
      - "~/www:/var/www:cached"

EOD;

        $this->manager->setDashtainerConfig($config)
            ->generateArchive(__DIR__);

        $this->assertEquals($expected, $this->manager->getDockerConfigYaml());
    }

    public function testHandlerWithBlackfire()
    {
        $config = Yaml::parse($this->getConfigWithBlackfire());

        $expected = <<<'EOD'
version: '3.1'
networks:
  web:
    external:
      name: traefik_webgateway
  unit-test-project: 
services:
  phpfpmservice:
    build:
      context: ./phpfpmservice
      dockerfile: Dockerfile
      args:
        SYSTEM_PACKAGES: ''
        PHP_MODULES: 'intl mysql xdebug xml'
        PEAR_MODULES: ''
        PECL_MODULES: ''
        INSTALL_COMPOSER: 1
        INSTALL_BLACKFIRE: 1
    environment:
      - BLACKFIRE_HOST=blackfire-phpfpmservice
    networks:
      - unit-test-project
      - web
    labels: {  }
    volumes:
      - "$PWD/phpfpmservice/php.ini:/etc/php/7.0/cli/conf.d/zzzz_custom.ini:delegated"
      - "$PWD/phpfpmservice/php.ini:/etc/php/7.0/fpm/conf.d/zzzz_custom.ini:delegated"
      - "$PWD/phpfpmservice/fpm.conf:/etc/php/7.0/fpm/php-fpm.conf:delegated"
      - "$PWD/phpfpmservice/fpm_pool.conf:/etc/php/7.0/fpm/pool.d/www.conf:delegated"
      - "~/www:/var/www:cached"
  blackfire-phpfpmservice:
    image: blackfire/blackfire
    environment:
      - BLACKFIRE_SERVER_ID=blackfire_server_id
      - BLACKFIRE_SERVER_TOKEN=blackfire_server_token
    networks:
      - unit-test-project
      - web
    volumes: {  }

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
    phpfpmservice:
        service_type: php-fpm
        name: phpfpmservice
        settings:
            version: 7.0
            modules:
                system: []
                php:
                    - intl
                    - mysql
                    - xml
                pear: []
                pecl: []
            ini:
                display_errors: On
            fpm_pool_ini:
                listen: 0.0.0.0:9000
            tools:
                xdebug:
                    install: 1
                    ini:
                        xdebug.default_enable: 1
                composer:
                    install: 1
                blackfire:
                    install: 0
                    settings: {  }
        networks:
            - web
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }

    protected function getConfigWithBlackfire() : string
    {
        return <<<'EOD'
project_name: unit-test-project
version: 1.0

services:
    phpfpmservice:
        service_type: php-fpm
        name: phpfpmservice
        settings:
            version: 7.0
            modules:
                system: []
                php:
                    - intl
                    - mysql
                    - xml
                pear: []
                pecl: []
            ini:
                display_errors: On
            fpm_pool_ini:
                listen: 0.0.0.0:9000
            tools:
                xdebug:
                    install: 1
                    ini:
                        xdebug.default_enable: 1
                composer:
                    install: 1
                blackfire:
                    install: 1
                    settings:
                        BLACKFIRE_SERVER_ID: blackfire_server_id
                        BLACKFIRE_SERVER_TOKEN: blackfire_server_token
        networks:
            - web
        volumes:
            -
                source: ~/www
                target: /var/www
                configuration: cached

EOD;
    }
}
