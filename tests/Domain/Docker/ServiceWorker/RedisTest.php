<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Redis;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class RedisTest extends ServiceWorkerBase
{
    /** @var Form\Service\RedisCreate */
    protected $form;

    /** @var Redis */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = Redis::getFormInstance();
        $this->form->name    = 'service-name';
        $this->form->version = '1.2';

        $this->worker = new Redis();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $this->assertEquals('redis:1.2', $this->service->getImage());
    }
}
