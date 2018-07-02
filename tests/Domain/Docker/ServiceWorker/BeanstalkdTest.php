<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Beanstalkd;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class BeanstalkdTest extends ServiceWorkerBase
{
    /** @var Form\Service\BeanstalkdCreate */
    protected $form;

    /** @var Beanstalkd */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = Beanstalkd::getFormInstance();
        $this->form->name = 'service-name';

        $this->worker = new Beanstalkd();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $build = $this->service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
    }
}
