<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PhpFpm;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;
use Dashtainer\Tests\Mock;

class PhpFpmTest extends ServiceWorkerBase
{
    /** @var Form\Service\PhpFpmCreate */
    protected $form;

    /** @var PhpFpm */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = PhpFpm::getFormInstance();
        $this->form->name      = 'service-name';
        $this->form->version   = 7.2;
        $this->form->ini       = [];
        $this->form->xdebug    = false;
        $this->form->blackfire = [
            'install'      => 0,
            'server_id'    => '',
            'server_token' => '',
        ];

        $this->service->setVersion($this->form->version);

        $this->worker = new PhpFpm();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType)
            ->setRepo(new Mock\RepoDockerService($this->getEm()));
    }

    public function testCreate()
    {
        $this->worker->create();

        $this->assertEmpty($this->service->getChildren());
    }

    public function testCreateAddsXdebug()
    {
        $this->form->xdebug = true;

        $this->worker->create();

        $env        = $this->service->getEnvironments();
        $xdebugMeta = $this->service->getMeta('xdebug');

        $this->assertEquals(':/etc/php/xdebug', $env['PHP_INI_SCAN_DIR']);
        $this->assertEquals(1, $xdebugMeta->getData()['install']);
    }

    public function testCreateAddsBlackfire()
    {
        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->create();

        $build = $this->service->getBuild();

        $this->assertEquals('blackfire-service-name', $build->getArgs()['BLACKFIRE_SERVER']);
    }

    public function testUpdate()
    {
        $this->worker->create();

        $this->form->ini    = [
            [
                'name' => 'ENV_VAR_1',
                'value' => 1,
            ],
            [
                'name' => 'ENV_VAR_2',
                'value' => 'value 2',
            ],
        ];
        $this->form->xdebug = true;

        $this->worker->update();

        $env        = $this->service->getEnvironments();
        $xdebugMeta = $this->service->getMeta('xdebug');

        $this->assertEquals(':/etc/php/xdebug', $env['PHP_INI_SCAN_DIR']);
        $this->assertEquals(1, $env['ENV_VAR_1']);
        $this->assertEquals('value 2', $env['ENV_VAR_2']);

        $this->assertEquals(1, $xdebugMeta->getData()['install']);
    }

    public function testUpdateAddsBlackfire()
    {
        $this->worker->create();

        $build = $this->service->getBuild();
        $args  = $build->getArgs();

        $this->assertArrayNotHasKey('BLACKFIRE_SERVER', $args);

        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->update();

        $build = $this->service->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals('blackfire-service-name', $args['BLACKFIRE_SERVER']);
    }

    public function testGetCreateParams()
    {
        $expected = [
            'ini'           => [
                [
                    'ini'   => 'display_errors',
                    'env'   => 'PHP_DISPLAY_ERRORS',
                    'value' => 'On',
                ],
                [
                    'ini'   => 'error_reporting',
                    'env'   => 'PHP_ERROR_REPORTING',
                    'value' => '-1',
                ],
                [
                    'ini'   => 'date.timezone',
                    'env'   => 'DATE_TIMEZONE',
                    'value' => 'UTC',
                ],
                [
                    'ini'   => 'xdebug.remote_host',
                    'env'   => 'XDEBUG_REMOTE_HOST',
                    'value' => 'host.docker.internal',
                ],
            ],
            'xdebug'        => false,
            'blackfire'     => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight' => 'ini',
        ];

        $result = $this->worker->getCreateParams();

        $this->assertEquals($expected, $result);
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $expected = [
            'ini'           => [],
            'xdebug'        => false,
            'blackfire'     => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight' => 'ini',
        ];

        $params = $this->worker->getViewParams();

        $this->assertEquals($expected, $params);
    }

    public function testGetViewParamsWithXdebugSelected()
    {
        $this->form->xdebug = true;

        $this->worker->create();

        $expected = [
            'ini'           => [],
            'xdebug'        => true,
            'blackfire'     => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight' => 'ini',
        ];

        $params = $this->worker->getViewParams();

        $this->assertEquals($expected, $params);
    }

    public function testGetViewParamsWithBlackfireSelected()
    {
        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->create();

        $blackfire = $this->createService('blackfire-service-name')
            ->setParent($this->service)
            ->setImage('blackfire/blackfire')
            ->setEnvironments([
                'BLACKFIRE_SERVER_ID'    => 'blackfire_server_id',
                'BLACKFIRE_SERVER_TOKEN' => 'blackfire_server_token',
            ]);

        $this->createServiceType('blackfire')
            ->addService($blackfire);

        $expected = [
            'ini'           => [],
            'xdebug'        => false,
            'blackfire'     => [
                'install'      => true,
                'server_id'    => 'blackfire_server_id',
                'server_token' => 'blackfire_server_token',
            ],
            'fileHighlight' => 'ini',
        ];

        $params = $this->worker->getViewParams();

        $this->assertEquals($expected, $params);
    }

    public function testManageChildrenReturnsEmptyArraysOnNoBlackfireChild()
    {
        $this->worker->create();

        $expected = [
            'create' => [],
            'update' => [],
            'delete' => [],
        ];

        $result = $this->worker->manageChildren();

        $this->assertEquals($expected, $result);
    }

    public function testManageChildrenReturnsCreateDetailsForNewBlackfireChild()
    {
        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->create();

        $this->createNetwork('network-a')
            ->addService($this->service);

        $result = $this->worker->manageChildren();

        $createResult = array_pop($result['create']);

        $this->assertEquals('blackfire', $createResult['serviceTypeSlug']);
        $this->assertTrue(is_a($createResult['form'], Form\Service\BlackfireCreate::class));

        /** @var Form\Service\BlackfireCreate $form */
        $form = $createResult['form'];

        $this->assertEquals($form->networks['network-a']['id'], 'network-a');
        $this->assertEquals($form->networks['network-a']['name'], 'network-a');

        $this->assertEquals('blackfire-service-name', $form->name);
        $this->assertEquals('blackfire_server_id', $form->server_id);
        $this->assertEquals('blackfire_server_token', $form->server_token);

        $this->assertEmpty($result['update']);
        $this->assertEmpty($result['delete']);
    }

    public function testManageChildrenReturnsUpdateDetailsForExistingBlackfireChild()
    {
        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->create();

        $this->createNetwork('network-a')
            ->addService($this->service);

        $blackfire = $this->createService('blackfire-service-name')
            ->setParent($this->service);

        $this->createServiceType('blackfire')
            ->addService($blackfire);

        $result = $this->worker->manageChildren();

        $updateResult = array_pop($result['update']);

        $this->assertSame($blackfire, $updateResult['service']);
        $this->assertTrue(is_a($updateResult['form'], Form\Service\BlackfireCreate::class));

        /** @var Form\Service\BlackfireCreate $form */
        $form = $updateResult['form'];

        $this->assertEquals($form->networks['network-a']['id'], 'network-a');
        $this->assertEquals($form->networks['network-a']['name'], 'network-a');

        $this->assertEmpty($result['create']);
        $this->assertEmpty($result['delete']);
    }

    public function testManageChildrenReturnsDeleteDetailsForExistingBlackfireChild()
    {
        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $this->worker->create();

        $this->form->blackfire = [
            'install'      => 0,
            'server_id'    => '',
            'server_token' => '',
        ];

        $this->worker->update();

        $blackfire = $this->createService('blackfire-service-name')
            ->setParent($this->service);

        $this->createServiceType('blackfire')
            ->addService($blackfire);

        $result = $this->worker->manageChildren();

        $deleteResult = array_pop($result['delete']);

        $this->assertSame($blackfire, $deleteResult);

        $this->assertEmpty($result['create']);
        $this->assertEmpty($result['update']);
    }
}
