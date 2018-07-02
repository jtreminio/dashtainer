<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MongoDB;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class MongoDBTest extends ServiceWorkerBase
{
    /** @var Form\Service\MongoDBCreate */
    protected $form;

    /** @var MongoDB */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = MongoDB::getFormInstance();
        $this->form->name    = 'service-name';
        $this->form->version = '1.2';

        $this->worker = new MongoDB();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $this->assertEquals('mongo:1.2', $this->service->getImage());
    }
}
