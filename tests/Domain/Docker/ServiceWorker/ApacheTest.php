<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Apache;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;
use Dashtainer\Tests\Mock;

class ApacheTest extends ServiceWorkerBase
{
    /** @var Form\Service\ApacheCreate */
    protected $form;

    /** @var Apache */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $phpfpmServiceType = $this->createServiceType('php-fpm');
        $phpServiceA = $this->createService('php-fpm-a')
            ->setType($phpfpmServiceType);
        $phpServiceB = $this->createService('php-fpm-b')
            ->setType($phpfpmServiceType);

        $nodeJsServiceType = $this->createServiceType('node-js');
        $portA = $this->createServiceMeta('port')
            ->setData([123]);
        $nodejsServiceA = $this->createService('nodejs-a')
            ->setType($nodeJsServiceType)
            ->addMeta($portA);

        $portB = $this->createServiceMeta('port')
            ->setData([123]);
        $nodejsServiceB = $this->createService('nodejs-b')
            ->setType($nodeJsServiceType)
            ->addMeta($portB);

        $this->service->getProject()
            ->addService($phpServiceA)
            ->addService($phpServiceB)
            ->addService($nodejsServiceA)
            ->addService($nodejsServiceB);

        $this->form = Apache::getFormInstance();
        $this->form->name          = 'service-name';
        $this->form->server_name   = 'server_name';
        $this->form->server_alias  = ['server_alias'];
        $this->form->document_root = '~/www/project';
        $this->form->handler       = 'php-fpm-7.2:9000';

        $this->worker = new Apache();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType)
            ->setRepo(new Mock\RepoDockerService($this->getEm()));
    }

    public function testCreate()
    {
        $this->worker->create();

        $labels = $this->service->getLabels();

        $expectedTraefikBackendLabel       = '{$COMPOSE_PROJECT_NAME}_service-name';
        $expectedTraefikDockerNetworkLabel = 'traefik_webgateway';
        $expectedTraefikFrontendRuleLabel  = 'Host:server_name,server_alias';

        $this->assertEquals($expectedTraefikBackendLabel, $labels['traefik.backend']);
        $this->assertEquals(
            $expectedTraefikDockerNetworkLabel,
            $labels['traefik.docker.network']
        );
        $this->assertEquals(
            $expectedTraefikFrontendRuleLabel,
            $labels['traefik.frontend.rule']
        );

        $expectedVhostMeta = [
            'server_name'   => $this->form->server_name,
            'server_alias'  => $this->form->server_alias,
            'document_root' => $this->form->document_root,
            'handler'       => $this->form->handler,
        ];
        $vhostMeta = $this->service->getMeta('vhost');
        $this->assertEquals($expectedVhostMeta, $vhostMeta->getData());
    }

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->server_name      = 'updatedServerName';
        $this->form->server_alias     = ['aliasA', 'aliasB'];
        $this->form->document_root    = '/path/to/glory';
        $this->form->handler          = '';

        $this->worker->update();

        $this->assertEquals(
            'Host:updatedServerName,aliasA,aliasB',
            $this->service->getLabels()['traefik.frontend.rule']
        );
    }

    public function testGetCreateParams()
    {
        $expected = [
            'vhost'         => [
                'server_name'   => 'awesome.localhost',
                'server_alias'  => ['www.awesome.localhost'],
                'document_root' => '/var/www',
                'handler'       => '',
            ],
            'handlers'      => [
                'PHP-FPM' => [
                    'php-fpm-a:9000',
                    'php-fpm-b:9000',
                ],
                'Node.js' => [
                    'nodejs-a:123',
                    'nodejs-b:123',
                ],
            ],
            'fileHighlight' => 'apacheconf',
        ];

        $result = $this->worker->getCreateParams();

        $this->assertEquals($expected, $result);
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $expected = [
            'vhost'         => [
                'server_name'   => 'server_name',
                'server_alias'  => ['server_alias'],
                'document_root' => '~/www/project',
                'handler'       => 'php-fpm-7.2:9000',
            ],
            'handlers'      => [
                'PHP-FPM' => [
                    'php-fpm-a:9000',
                    'php-fpm-b:9000',
                ],
                'Node.js' => [
                    'nodejs-a:123',
                    'nodejs-b:123',
                ],
            ],
            'fileHighlight' => 'apacheconf',
        ];

        $params = $this->worker->getViewParams();

        $this->assertEquals($expected, $params);
    }
}
