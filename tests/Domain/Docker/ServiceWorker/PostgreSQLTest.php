<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PostgreSQL;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class PostgreSQLTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\PostgreSQLCreate */
    protected $form;

    /** @var PostgreSQL */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\PostgreSQLCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'postgresql.conf' => 'postgresql.conf contents',
        ];
        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->form->postgres_db       = 'dbname';
        $this->form->postgres_user     = 'dbuser';
        $this->form->postgres_password = 'userpw';

        $this->worker = new PostgreSQL(
            $this->serviceRepo,
            $this->serviceTypeRepo,
            $this->networkDomain,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $environment = $service->getEnvironments();

        $this->assertEquals(
            '/run/secrets/service-name-postgres_db',
            $environment['POSTGRES_DB_FILE']
        );
        $this->assertEquals(
            '/run/secrets/service-name-postgres_user',
            $environment['POSTGRES_USER_FILE']
        );
        $this->assertEquals(
            '/run/secrets/service-name-postgres_password',
            $environment['POSTGRES_PASSWORD_FILE']
        );

        $secret_postgres_db       = $service->getSecret('service-name-postgres_db');
        $secret_postgres_user     = $service->getSecret('service-name-postgres_user');
        $secret_postgres_password = $service->getSecret('service-name-postgres_password');

        $this->assertEquals(
            $this->form->postgres_db,
            $secret_postgres_db->getProjectSecret()->getContents()
        );
        $this->assertEquals(
            $this->form->postgres_user,
            $secret_postgres_user->getProjectSecret()->getContents()
        );
        $this->assertEquals(
            $this->form->postgres_password,
            $secret_postgres_password->getProjectSecret()->getContents()
        );

        $configFileVolume = $service->getVolume('postgresql.conf');

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
            5433
        ];

        yield [
            [5433, 5434, 5435],
            5436
        ];

        yield [
            [5433, 5435, 5437],
            5434
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('postgresql.conf'),
            $params['systemFiles']['postgresql.conf']
        );

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(5433, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
        $this->assertEquals($this->form->postgres_db, $params['postgres_db']);
        $this->assertEquals($this->form->postgres_user, $params['postgres_user']);
        $this->assertEquals($this->form->postgres_password, $params['postgres_password']);
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->postgres_db       = 'newdb';
        $form->postgres_user     = 'newuser';
        $form->postgres_password = 'newuserpw';

        $form->port_confirm = true;
        $form->port         = 3307;

        $form->system_file['postgresql.conf'] = 'new configfile data';

        $updatedService = $this->worker->update($service, $form);

        $uConfigFileVol = $updatedService->getVolume('postgresql.conf');
        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();
        $uEnvironments  = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'POSTGRES_DB_FILE'       => '/run/secrets/service-name-postgres_db',
            'POSTGRES_USER_FILE'     => '/run/secrets/service-name-postgres_user',
            'POSTGRES_PASSWORD_FILE' => '/run/secrets/service-name-postgres_password',
        ];

        $expectedServicePorts = ["{$form->port}:5432"];

        $this->assertEquals($form->system_file['postgresql.conf'], $uConfigFileVol->getData());
        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
