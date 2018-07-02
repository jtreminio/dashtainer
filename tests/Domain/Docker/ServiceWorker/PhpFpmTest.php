<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PhpFpm;
use Dashtainer\Entity\Docker as Entity;
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

        $phpPackagesMeta = $this->createServiceTypeMeta('packages-7.2')
            ->setData([
                'default'   => ['php-packageA'],
                'available' => ['php-packageA', 'php-packageB', 'php-packageC'],
            ]);

        $phpGeneralPackagesMeta = $this->createServiceTypeMeta('packages-general')
            ->setData([
                'default'   => ['general-packageA'],
                'available' => ['general-packageA', 'general-packageB', 'general-packageC'],
            ]);

        $this->serviceType->addMeta($phpPackagesMeta)
            ->addMeta($phpGeneralPackagesMeta);

        $this->form = PhpFpm::getFormInstance();
        $this->form->name            = 'service-name';
        $this->form->version         = 7.2;
        $this->form->php_packages    = ['php-packageA', 'php-packageB', 'php-packageA'];
        $this->form->pear_packages   = ['pear-packageA', 'pear-packageB', 'pear-packageA'];
        $this->form->pecl_packages   = ['pecl-packageA', 'pecl-packageB', 'pecl-packageA'];
        $this->form->system_packages = ['system-packageA', 'system-packageB', 'system-packageA'];
        $this->form->composer        = ['install' => 1];
        $this->form->xdebug          = ['install' => 0];
        $this->form->blackfire       = [
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

        $build = $this->service->getBuild();

        $expectedArgs = [
            'SYSTEM_PACKAGES'  => ['system-packageA', 'system-packageB'],
            'PHP_PACKAGES'     => ['php-packageA', 'php-packageB'],
            'PEAR_PACKAGES'    => ['pear-packageA', 'pear-packageB'],
            'PECL_PACKAGES'    => ['pecl-packageA', 'pecl-packageB'],
            'COMPOSER_INSTALL' => 1,
        ];

        $this->assertEquals($expectedArgs, $build->getArgs());
        $this->assertEmpty($this->service->getChildren());
    }

    public function testCreateAddsXdebug()
    {
        $this->form->xdebug = ['install' => 1];

        $this->worker->create();

        $build = $this->service->getBuild();

        $expectedArgs = [
            'SYSTEM_PACKAGES'  => ['system-packageA', 'system-packageB'],
            'PHP_PACKAGES'     => ['php-packageA', 'php-packageB', 'php-xdebug'],
            'PEAR_PACKAGES'    => ['pear-packageA', 'pear-packageB'],
            'PECL_PACKAGES'    => ['pecl-packageA', 'pecl-packageB'],
            'COMPOSER_INSTALL' => 1,
        ];

        $this->assertEquals($expectedArgs, $build->getArgs());
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

        $this->form->system_packages = [];
        $this->form->php_packages    = [];
        $this->form->pear_packages   = [];
        $this->form->pecl_packages   = [];
        $this->form->composer        = 0;

        $this->worker->update();

        $build = $this->service->getBuild();
        $args  = $build->getArgs();

        $expectedArgs = [
            'SYSTEM_PACKAGES'  => [],
            'PHP_PACKAGES'     => [],
            'PEAR_PACKAGES'    => [],
            'PECL_PACKAGES'    => [],
            'COMPOSER_INSTALL' => 0,
        ];

        $this->assertEquals($expectedArgs, $args);
    }

    public function testUpdateAddsXdebug()
    {
        $this->worker->create();

        $build = $this->service->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals(['php-packageA', 'php-packageB'], $args['PHP_PACKAGES']);

        $this->form->xdebug = ['install' => 1,];

        $this->worker->update();

        $build = $this->service->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals(['php-packageA', 'php-packageB', 'php-xdebug'], $args['PHP_PACKAGES']);
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
            'phpPackagesSelected'    => ['php-packageA'],
            'phpPackagesAvailable'   => ['php-packageA', 'php-packageB', 'php-packageC'],
            'pearPackagesSelected'   => [],
            'peclPackagesSelected'   => [],
            'systemPackagesSelected' => [],
            'composer'               => ['install' => true],
            'xdebug'                 => ['install' => false],
            'blackfire'              => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight'          => 'ini',
        ];

        $result = $this->worker->getCreateParams();

        $this->assertEquals($expected, $result);
    }

    public function testGetViewParams()
    {
        $this->worker->create();

        $expected = [
            'phpPackagesSelected'    => ['php-packageA', 'php-packageB'],
            'phpPackagesAvailable'   => ['php-packageC'],
            'pearPackagesSelected'   => ['pear-packageA', 'pear-packageB'],
            'peclPackagesSelected'   => ['pecl-packageA', 'pecl-packageB'],
            'systemPackagesSelected' => ['system-packageA', 'system-packageB'],
            'composer'               => ['install' => true],
            'xdebug'                 => ['install' => false],
            'blackfire'              => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight'          => 'ini',
        ];

        $params = $this->worker->getViewParams();

        $this->assertEquals($expected, $params);
    }

    public function testGetViewParamsWithXdebugSelected()
    {
        $this->form->xdebug = [
            'install' => 1,
            'ini'     => 'xdebug ini',
            'cli_ini' => 'xdebug cli ini',
        ];

        $this->worker->create();

        $expected = [
            'phpPackagesSelected'    => ['php-packageA', 'php-packageB', 'php-xdebug'],
            'phpPackagesAvailable'   => ['php-packageC'],
            'pearPackagesSelected'   => ['pear-packageA', 'pear-packageB'],
            'peclPackagesSelected'   => ['pecl-packageA', 'pecl-packageB'],
            'systemPackagesSelected' => ['system-packageA', 'system-packageB'],
            'composer'               => ['install' => true],
            'xdebug'                 => ['install' => true],
            'blackfire'              => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight'          => 'ini',
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
            'phpPackagesSelected'    => ['php-packageA', 'php-packageB'],
            'phpPackagesAvailable'   => ['php-packageC'],
            'pearPackagesSelected'   => ['pear-packageA', 'pear-packageB'],
            'peclPackagesSelected'   => ['pecl-packageA', 'pecl-packageB'],
            'systemPackagesSelected' => ['system-packageA', 'system-packageB'],
            'composer'               => ['install' => true],
            'xdebug'                 => ['install' => false],
            'blackfire'              => [
                'install'      => true,
                'server_id'    => 'blackfire_server_id',
                'server_token' => 'blackfire_server_token',
            ],
            'fileHighlight'          => 'ini',
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
