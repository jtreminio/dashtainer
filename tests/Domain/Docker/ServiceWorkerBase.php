<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker\Service\CreateAbstract;

class ServiceWorkerBase extends DomainAbstract
{
    /** @var CreateAbstract */
    protected $form;

    /** @var Entity\Service */
    protected $service;

    /** @var Entity\ServiceType */
    protected $serviceType;

    protected function setUp()
    {
        parent::setUp();

        $project = $this->createProject('project-name');
        $project->addNetwork($this->createPrivateNetwork())
            ->addNetwork($this->createPublicNetwork());

        $this->serviceType = $this->createServiceType('service-type-name');

        $this->service = $this->createService('service');
        $this->service->setType($this->serviceType)
            ->setProject($project);
    }
}
