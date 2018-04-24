<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MariaDB;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class MariaDBTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\MariaDBCreate */
    protected $form;

    /** @var MariaDB */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\MariaDBCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'my.cnf'          => 'my.cnf contents',
            'config-file.cnf' => 'config-file.cnf contents',
        ];
        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->form->mysql_root_password = 'rootpw';
        $this->form->mysql_database      = 'dbname';
        $this->form->mysql_user          = 'dbuser';
        $this->form->mysql_password      = 'userpw';

        $this->worker = new MariaDB($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $environment = $service->getEnvironments();

        $this->assertEquals('rootpw', $environment['MYSQL_ROOT_PASSWORD']);
        $this->assertEquals('dbname', $environment['MYSQL_DATABASE']);
        $this->assertEquals('dbuser', $environment['MYSQL_USER']);
        $this->assertEquals('userpw', $environment['MYSQL_PASSWORD']);

        $myCnfVolume      = $service->getVolume('my.cnf');
        $configFileVolume = $service->getVolume('config-file.cnf');

        $this->assertNotNull($myCnfVolume);
        $this->assertNotNull($configFileVolume);
        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getMeta('version'));
        $this->assertNotNull($service->getMeta('bind-port'));
    }

    /**
     * @var array $usedPorts
     * @var int   $openPort
     * @dataProvider providerGetCreateParamsReturnsFirstUnusedBindPort
     */
    public function testGetCreateParamsReturnsFirstUnusedBindPort(array $usedPorts, int $openPort)
    {
        foreach ($usedPorts as $port) {
            $meta = new Entity\Docker\ServiceMeta();
            $meta->setName('bind-port')
                ->setData([$port]);

            $service = new Entity\Docker\Service();
            $service->addMeta($meta);

            $this->project->addService($service);
        }

        $this->form->port         = $openPort;
        $this->form->port_confirm = true;

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($openPort, $params['bindPort']);
    }

    public function providerGetCreateParamsReturnsFirstUnusedBindPort()
    {
        yield [
            [],
            3307
        ];

        yield [
            [3307, 3308, 3309],
            3310
        ];

        yield [
            [3307, 3309, 3311],
            3308
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('my.cnf'),
            $params['systemFiles']['my.cnf']
        );
        $this->assertSame(
            $service->getVolume('config-file.cnf'),
            $params['systemFiles']['config-file.cnf']
        );

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(3307, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
        $this->assertEquals($this->form->mysql_root_password, $params['mysql_root_password']);
        $this->assertEquals($this->form->mysql_database, $params['mysql_database']);
        $this->assertEquals($this->form->mysql_user, $params['mysql_user']);
        $this->assertEquals($this->form->mysql_password, $params['mysql_password']);
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->mysql_root_password = 'newrootpw';
        $form->mysql_database      = 'newdb';
        $form->mysql_user          = 'newuser';
        $form->mysql_password      = 'newuserpw';

        $form->port_confirm = true;
        $form->port         = 3307;

        $form->system_file['my.cnf']          = 'new_mycnf data';
        $form->system_file['config-file.cnf'] = 'new configfile data';

        $updatedService = $this->worker->update($service, $form);

        $uMyCnfVol      = $updatedService->getVolume('my.cnf');
        $uConfigFileVol = $updatedService->getVolume('config-file.cnf');
        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();
        $uEnvironments  = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ];

        $expectedServicePorts = ["{$form->port}:3306"];

        $this->assertEquals($form->system_file['my.cnf'], $uMyCnfVol->getData());
        $this->assertEquals($form->system_file['config-file.cnf'], $uConfigFileVol->getData());
        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
