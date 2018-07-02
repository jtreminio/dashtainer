<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MailHog;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class MailHogTest extends ServiceWorkerBase
{
    /** @var Form\Service\MailHogCreate */
    protected $form;

    /** @var MailHog */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $this->form = MailHog::getFormInstance();
        $this->form->name = 'service-name';

        $this->worker = new MailHog();
        $this->worker->setForm($this->form)
            ->setService($this->service)
            ->setServiceType($this->serviceType);
    }

    public function testCreate()
    {
        $this->worker->create();

        $labels = $this->service->getLabels();

        $this->assertSame($this->form->name, $this->service->getName());
        $this->assertEquals('mailhog/mailhog:latest', $this->service->getImage());

        $expectedTraefikBackendLabel       = '{$COMPOSE_PROJECT_NAME}_service-name';
        $expectedTraefikDockerNetworkLabel = 'traefik_webgateway';
        $expectedTraefikFrontendRuleLabel  = 'Host:service-name.localhost';
        $expectedTraefikPortLabel          = '8025';

        $this->assertEquals($expectedTraefikBackendLabel, $labels['traefik.backend']);
        $this->assertEquals(
            $expectedTraefikDockerNetworkLabel,
            $labels['traefik.docker.network']
        );
        $this->assertEquals(
            $expectedTraefikFrontendRuleLabel,
            $labels['traefik.frontend.rule']
        );
        $this->assertEquals(
            $expectedTraefikPortLabel,
            $labels['traefik.port']
        );
    }
}
