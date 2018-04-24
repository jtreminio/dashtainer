<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Beanstalkd;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class BeanstalkdTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\BeanstalkdCreate */
    protected $form;

    /** @var Beanstalkd */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = new Form\Docker\Service\BeanstalkdCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'Dockerfile' => 'Dockerfile contents',
        ];
        $this->form->datastore   = 'local';

        $this->worker = new Beanstalkd($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $this->assertNotNull($service->getVolume('Dockerfile'));
    }

    public function testGetCreateParams()
    {
        $this->assertEquals([], $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals('local', $params['datastore']);
        $this->assertSame(
            $service->getVolume('Dockerfile'),
            $params['systemFiles']['Dockerfile']
        );
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->system_file['Dockerfile'] = 'new dockerfile data';

        $updatedService = $this->worker->update($service, $form);

        $uDockerfileVol = $updatedService->getVolume('Dockerfile');

        $this->assertEquals($form->system_file['Dockerfile'], $uDockerfileVol->getData());
    }
}
