<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PhpFpm;
use Dashtainer\Domain\Docker\ServiceWorker\Blackfire;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PhpFpmTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\PhpFpmCreate */
    protected $form;

    /** @var PhpFpm */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $moduleMeta = new Entity\Docker\ServiceTypeMeta();
        $moduleMeta->setName('php-fpm-startup')
            ->setData(['php-fpm-startup data']);

        $phpPackagesMeta = new Entity\Docker\ServiceTypeMeta();
        $phpPackagesMeta->setName('packages-7.2')
            ->setData([
                'default'   => ['php-packageA'],
                'available' => ['php-packageA', 'php-packageB', 'php-packageC'],
            ]);

        $phpGeneralPackagesMeta = new Entity\Docker\ServiceTypeMeta();
        $phpGeneralPackagesMeta->setName('packages-general')
            ->setData([
                'default'   => ['general-packageA'],
                'available' => ['general-packageA', 'general-packageB', 'general-packageC'],
            ]);

        $xdebugIniMeta = new Entity\Docker\ServiceTypeMeta();
        $xdebugIniMeta->setName('ini-xdebug')
            ->setData(['ini-xdebug meta data']);

        $this->serviceType->addMeta($moduleMeta)
            ->addMeta($phpPackagesMeta)
            ->addMeta($phpGeneralPackagesMeta)
            ->addMeta($xdebugIniMeta);

        $this->form = new Form\Docker\Service\PhpFpmCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->version         = 7.2;
        $this->form->php_packages    = ['php-packageA', 'php-packageB', 'php-packageA'];
        $this->form->pear_packages   = ['pear-packageA', 'pear-packageB', 'pear-packageA'];
        $this->form->pecl_packages   = ['pecl-packageA', 'pecl-packageB', 'pecl-packageA'];
        $this->form->system_packages = ['system-packageA', 'system-packageB', 'system-packageA'];

        $this->form->composer  = ['install' => 1];
        $this->form->xdebug    = ['install' => 0, 'ini' => []];
        $this->form->blackfire = [
            'install'      => 0,
            'server_id'    => '',
            'server_token' => '',
        ];

        $this->form->project_files = [
            'type'  => 'local',
            'local' => [
                'source' => '~/www/project',
            ]
        ];

        $this->form->system_file = [
            'Dockerfile'   => 'Dockerfile contents',
            'php.ini'      => 'php.ini contents',
            'php-fpm.conf' => 'php-fpm.conf contents',
        ];

        $this->form->networks = [
            'new-network-a',
            'new-network-b',
            'private-network-a',
        ];

        $blackfireWorker = new Blackfire(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo
        );

        $this->worker = new PhpFpm(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $blackfireWorker
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();

        $expectedArgs = [
            'SYSTEM_PACKAGES'   => ['system-packageA', 'system-packageB'],
            'PHP_PACKAGES'      => ['php-packageA', 'php-packageB'],
            'PEAR_PACKAGES'     => ['pear-packageA', 'pear-packageB'],
            'PECL_PACKAGES'     => ['pecl-packageA', 'pecl-packageB'],
            'COMPOSER_INSTALL'  => 1,
            'BLACKFIRE_INSTALL' => 0,
        ];
        $this->assertEquals($expectedArgs, $build->getArgs());

        $this->assertNotNull($service->getMeta('version'));

        $dockerfileVolume    = $service->getVolume('Dockerfile');
        $phpIniVolume        = $service->getVolume('php.ini');
        $phpCliVolume        = $service->getVolume('php-cli.ini');
        $phpFpmVolume        = $service->getVolume('php-fpm.conf');
        $phpFpmStartupVolume = $service->getVolume('php-fpm-startup');
        $xdebugVolume        = $service->getVolume('xdebug.ini');
        $xdebugCliVolume     = $service->getVolume('xdebug-cli.ini');

        $this->assertNotNull($dockerfileVolume);
        $this->assertNotNull($phpIniVolume);
        $this->assertNotNull($phpCliVolume);
        $this->assertNotNull($phpFpmVolume);
        $this->assertNotNull($phpFpmStartupVolume);

        $this->assertNull($xdebugVolume);
        $this->assertNull($xdebugCliVolume);

        $this->assertEmpty($service->getChildren());

        $this->assertCount(3, $service->getNetworks());
    }

    public function testCreateAddsXdebug()
    {
        $this->networkRepoDefaultExpects();

        $this->form->xdebug = [
            'install' => 1,
            'ini'     => 'xdebug ini'
        ];

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();

        $this->assertContains('php-xdebug', $build->getArgs()['PHP_PACKAGES']);

        $xdebugVolume    = $service->getVolume('xdebug.ini');
        $xdebugCliVolume = $service->getVolume('xdebug-cli.ini');

        $this->assertNotNull($xdebugVolume);
        $this->assertNotNull($xdebugCliVolume);
    }

    public function testCreateAddsBlackfire()
    {
        $worker = new PhpFpm(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $service = $worker->create($this->form);
        /** @var Entity\Docker\Service $blackfire */
        $blackfire = $service->getChildren()->first();

        $build = $service->getBuild();

        $this->assertCount(1, $service->getChildren());

        $this->assertEquals(1, $build->getArgs()['BLACKFIRE_INSTALL']);
        $this->assertCount(4, $service->getNetworks());
        $this->assertCount(4, $blackfire->getNetworks());

        $blackfireNetworkMeta = $service->getMeta('blackfire-network');
        $this->assertEquals(['service-name-blackfire'], $blackfireNetworkMeta->getData());

        $this->assertSame($service, $blackfire->getParent());
    }

    public function testGetViewParams()
    {
        $blackfireServiceType = new Entity\Docker\ServiceType();
        $blackfireServiceType->setName('blackfire-service-type');

        $this->serviceTypeRepo->expects($this->atLeastOnce())
            ->method('findBySlug')
            ->with('blackfire')
            ->will($this->returnValue($blackfireServiceType));

        $this->serviceRepo->expects($this->once())
            ->method('findChildByType')
            ->will($this->returnValue(null));

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $expectedPhpPackagesSelected    = ['php-packageA', 'php-packageB'];
        $expectedPhpPackagesAvailable   = ['php-packageC'];
        $expectedPearPackagesSelected   = ['pear-packageA', 'pear-packageB'];
        $expectedPeclPackagesSelected   = ['pecl-packageA', 'pecl-packageB'];
        $expectedSystemPackagesSelected = ['system-packageA', 'system-packageB'];

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals($expectedPhpPackagesSelected, array_values($params['phpPackagesSelected']));
        $this->assertEquals($expectedPhpPackagesAvailable, array_values($params['phpPackagesAvailable']));
        $this->assertEquals($expectedPearPackagesSelected, array_values($params['pearPackagesSelected']));
        $this->assertEquals($expectedPeclPackagesSelected, array_values($params['peclPackagesSelected']));
        $this->assertEquals($expectedSystemPackagesSelected, array_values($params['systemPackagesSelected']));

        $this->assertSame(
            $service->getVolume('php.ini'),
            $params['systemFiles']['php.ini']
        );
        $this->assertSame(
            $service->getVolume('php-fpm.conf'),
            $params['systemFiles']['php-fpm.conf']
        );

        $expectedComposer  = ['install' => 1];
        $expectedXdebug    = ['install' => false, 'ini' => 'ini-xdebug meta data'];
        $expectedBlackfire = ['install' => 0, 'server_id' => '', 'server_token' => ''];

        $this->assertEquals($expectedComposer, $params['composer']);
        $this->assertEquals($expectedXdebug, $params['xdebug']);
        $this->assertEquals($expectedBlackfire, $params['blackfire']);
    }

    public function testGetViewParamsWithXdebugSelected()
    {
        $blackfireServiceType = new Entity\Docker\ServiceType();
        $blackfireServiceType->setName('blackfire-service-type');

        $this->serviceTypeRepo->expects($this->atLeastOnce())
            ->method('findBySlug')
            ->with('blackfire')
            ->will($this->returnValue($blackfireServiceType));

        $this->serviceRepo->expects($this->once())
            ->method('findChildByType')
            ->will($this->returnValue(null));

        $this->form->xdebug = [
            'install' => 1,
            'ini'     => 'xdebug ini'
        ];

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $expectedPhpPackagesSelected = ['php-packageA', 'php-packageB', 'php-xdebug'];

        $this->assertEquals($expectedPhpPackagesSelected, array_values($params['phpPackagesSelected']));

        $expectedXdebug = ['install' => 1, 'ini' => 'xdebug ini'];

        $this->assertEquals($expectedXdebug, $params['xdebug']);
    }

    public function testGetViewParamsWithBlackfireSelected()
    {
        $worker = new PhpFpm(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $service   = $worker->create($this->form);
        /** @var Entity\Docker\Service $blackfire */
        $blackfire = $service->getChildren()->first();

        $viewWorker = $this->getPhpFpmWorkerForExistingBlackfireService($blackfire);

        $params = $viewWorker->getViewParams($service);

        $bfEnv = $blackfire->getEnvironments();

        $this->assertEquals($bfEnv['BLACKFIRE_SERVER_ID'], $params['blackfire']['server_id']);
    }

    public function testUpdate()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new PhpFpm(
            $this->serviceRepo,
            $networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $form = clone $this->form;

        $form->system_packages = [];
        $form->php_packages    = [];
        $form->pear_packages   = [];
        $form->pecl_packages   = [];
        $form->composer        = 0;

        $form->system_file['Dockerfile']   = 'new dockerfile data';
        $form->system_file['php.ini']      = 'new php ini data';
        $form->system_file['php-fpm.conf'] = 'new php-fpm.conf data';

        $updatedService = $worker->update($service, $form);

        $build = $updatedService->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals($form->system_packages, $args['SYSTEM_PACKAGES']);
        $this->assertEquals($form->php_packages, $args['PHP_PACKAGES']);
        $this->assertEquals($form->pear_packages, $args['PEAR_PACKAGES']);
        $this->assertEquals($form->pecl_packages, $args['PECL_PACKAGES']);
        $this->assertEquals($form->composer, $args['COMPOSER_INSTALL']);

        $dockerFileVol   = $updatedService->getVolume('Dockerfile');
        $phpIniVol       = $updatedService->getVolume('php.ini');
        $phpCliIniVol    = $updatedService->getVolume('php-cli.ini');
        $phpFpmConfVol   = $updatedService->getVolume('php-fpm.conf');

        $xdebugIniVol    = $updatedService->getVolume('xdebug.ini');
        $xdebugCliIniVol = $updatedService->getVolume('xdebug-cli.ini');

        $this->assertEquals($form->system_file['Dockerfile'], $dockerFileVol->getData());
        $this->assertEquals($form->system_file['php.ini'], $phpIniVol->getData());
        $this->assertEquals($form->system_file['php.ini'], $phpCliIniVol->getData());
        $this->assertEquals($form->system_file['php-fpm.conf'], $phpFpmConfVol->getData());

        $this->assertNull($xdebugIniVol);
        $this->assertNull($xdebugCliIniVol);

        $this->assertCount(0, $service->getChildren());
    }

    public function testUpdateAddsXdebug()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new PhpFpm(
            $this->serviceRepo,
            $networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $form = clone $this->form;

        $form->xdebug['install'] = 1;
        $form->xdebug['ini']     = 'xdebug ini';

        $updatedService = $worker->update($service, $form);

        $build = $updatedService->getBuild();
        $args  = $build->getArgs();

        $this->assertContains('php-xdebug', $args['PHP_PACKAGES']);

        $xdebugIniVol    = $updatedService->getVolume('xdebug.ini');
        $xdebugCliIniVol = $updatedService->getVolume('xdebug-cli.ini');

        $this->assertEquals($form->xdebug['ini'], $xdebugIniVol->getData());
        $this->assertEquals($form->xdebug['ini'], $xdebugCliIniVol->getData());
    }

    public function testUpdateAddsBlackfire()
    {
        $this->networkRepoDefaultExpects();

        $service = $this->worker->create($this->form);

        $networkRepo = $this->getUpdateNetworkRepo();

        $worker = new PhpFpm(
            $this->serviceRepo,
            $networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $form = clone $this->form;

        $form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $updatedService = $worker->update($service, $form);
        /** @var Entity\Docker\Service $blackfire */
        $blackfire = $service->getChildren()->first();

        $build = $updatedService->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals(1, $args['BLACKFIRE_INSTALL']);

        $this->assertNotNull($blackfire);
    }

    public function testUpdateUpdatesBlackfire()
    {
        $this->networkRepoDefaultExpects();

        $this->form->blackfire = [
            'install'      => 1,
            'server_id'    => 'blackfire_server_id',
            'server_token' => 'blackfire_server_token',
        ];

        $worker = new PhpFpm(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->getBlackfireWorkerForNewService()
        );

        $service = $worker->create($this->form);

        /** @var Entity\Docker\Service $blackfire */
        $blackfire = $service->getChildren()->first();

        $updateWorker = $this->getPhpFpmWorkerForExistingBlackfireService($blackfire);

        $form = clone $this->form;

        $form->blackfire = [
            'install'      => 1,
            'server_id'    => 'new_blackfire_server_id',
            'server_token' => 'new_blackfire_server_token',
        ];

        $updatedService = $updateWorker->update($service, $form);
        /** @var Entity\Docker\Service $updatedBlackfire */
        $updatedBlackfire = $service->getChildren()->first();

        $build = $updatedService->getBuild();
        $args  = $build->getArgs();

        $this->assertEquals(1, $args['BLACKFIRE_INSTALL']);

        $expectedBfEnv = [
            'BLACKFIRE_SERVER_ID'    => $form->blackfire['server_id'],
            'BLACKFIRE_SERVER_TOKEN' => $form->blackfire['server_token'],
        ];

        $envBlackfire = $updatedBlackfire->getEnvironments();

        $this->assertEquals($expectedBfEnv, $envBlackfire);
    }

    protected function getBlackfireWorkerForNewService() : Blackfire
    {
        $blackfireServiceType = new Entity\Docker\ServiceType();
        $blackfireServiceType->setName('blackfire-service-type');

        $this->serviceTypeRepo->expects($this->any())
            ->method('findBySlug')
            ->with('blackfire')
            ->will($this->returnValue($blackfireServiceType));

        $this->serviceRepo->expects($this->any())
            ->method('findChildByType')
            ->will($this->returnValue(null));

        /** @var MockObject|Repository\Docker\Network $blackfireNetworkRepo */
        $blackfireNetworkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $blackfireNetworkRepo->expects($this->any())
            ->method('getPublicNetwork')
            ->will($this->returnValue($this->publicNetwork));

        $blackfireNetworkRepo->expects($this->any())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $blackfireNetworkRepo->expects($this->any())
            ->method('findByService')
            ->will($this->returnValue([]));

        return new Blackfire(
            $this->serviceRepo,
            $blackfireNetworkRepo,
            $this->serviceTypeRepo
        );
    }

    protected function getPhpFpmWorkerForExistingBlackfireService(
        Entity\Docker\Service $blackfire
    ) : PhpFpm {
        /** @var MockObject|Repository\Docker\Service $serviceRepo */
        $serviceRepo = $this->getMockBuilder(Repository\Docker\Service::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $blackfireServiceType = new Entity\Docker\ServiceType();
        $blackfireServiceType->setName('blackfire-service-type');

        $this->serviceTypeRepo->expects($this->atLeastOnce())
            ->method('findBySlug')
            ->with('blackfire')
            ->will($this->returnValue($blackfireServiceType));

        $serviceRepo->expects($this->atLeastOnce())
            ->method('findChildByType')
            ->will($this->returnValue($blackfire));

        /** @var MockObject|Repository\Docker\Network $blackfireNetworkRepo */
        $blackfireNetworkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$this->em])
            ->getMock();

        $blackfireNetworkRepo->expects($this->any())
            ->method('getPublicNetwork')
            ->will($this->returnValue($this->publicNetwork));

        $blackfireNetworkRepo->expects($this->any())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $blackfireNetworkRepo->expects($this->any())
            ->method('findByService')
            ->will($this->returnValue([]));

        $blackfireWorker = new Blackfire(
            $serviceRepo,
            $blackfireNetworkRepo,
            $this->serviceTypeRepo
        );

        return new PhpFpm(
            $serviceRepo,
            $blackfireNetworkRepo,
            $this->serviceTypeRepo,
            $blackfireWorker
        );
    }
}
