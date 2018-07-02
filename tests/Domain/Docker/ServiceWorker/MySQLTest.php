<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MySQL;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class MySQLTest extends ServiceWorkerBase
{
    /** @var Form\Service\MySQLCreate */
    protected $form;

    /** @var MySQL */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = MySQL::getFormInstance();
        $this->form->name    = 'service-name';
        $this->form->version = '1.2';

        $this->worker = new MySQL();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $environment = $this->service->getEnvironments();

        $this->assertEquals(
            '/run/secrets/mysql_root_password',
            $environment['MYSQL_ROOT_PASSWORD_FILE']
        );
        $this->assertEquals(
            '/run/secrets/mysql_database',
            $environment['MYSQL_DATABASE_FILE']
        );
        $this->assertEquals(
            '/run/secrets/mysql_user',
            $environment['MYSQL_USER_FILE']
        );
        $this->assertEquals(
            '/run/secrets/mysql_password',
            $environment['MYSQL_PASSWORD_FILE']
        );

        $this->assertEquals('mysql:1.2', $this->service->getImage());
    }
}
