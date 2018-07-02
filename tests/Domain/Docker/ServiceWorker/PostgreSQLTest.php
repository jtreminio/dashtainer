<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PostgreSQL;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class PostgreSQLTest extends ServiceWorkerBase
{
    /** @var Form\Service\PostgreSQLCreate */
    protected $form;

    /** @var PostgreSQL */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = PostgreSQL::getFormInstance();
        $this->form->name    = 'service-name';
        $this->form->version = '1.2';

        $this->worker = new PostgreSQL();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $environment = $this->service->getEnvironments();

        $this->assertEquals(
            '/run/secrets/postgres_db',
            $environment['POSTGRES_DB_FILE']
        );
        $this->assertEquals(
            '/run/secrets/postgres_user',
            $environment['POSTGRES_USER_FILE']
        );
        $this->assertEquals(
            '/run/secrets/postgres_password',
            $environment['POSTGRES_PASSWORD_FILE']
        );

        $this->assertEquals('postgres:1.2', $this->service->getImage());
    }
}
