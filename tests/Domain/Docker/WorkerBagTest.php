<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker as Domain;
use Dashtainer\Tests\Mock;

class WorkerBagTest extends DomainAbstract
{
    /** @var Domain\WorkerBag */
    protected $bag;

    protected function setUp()
    {
        $workers = [
            new Mock\DomainDockerServiceWorkerA(),
            new Mock\DomainDockerServiceWorkerB(),
        ];

        $serviceTypeA = $this->createServiceType(Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG);
        $serviceTypeB = $this->createServiceType(Mock\DomainDockerServiceWorkerB::SERVICE_TYPE_SLUG);

        $repo = new Mock\RepoDockerServiceType($this->getEm());
        $repo->addServiceType($serviceTypeA);
        $repo->addServiceType($serviceTypeB);

        $serviceType = new Domain\ServiceType($repo);

        $this->bag = new Domain\WorkerBag($workers, $serviceType);
    }

    public function testGetWorkerFromType()
    {
        $workerA = $this->bag->getWorkerFromType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );

        $workerB = $this->bag->getWorkerFromType(
            Mock\DomainDockerServiceWorkerB::SERVICE_TYPE_SLUG
        );

        $this->assertTrue(is_a($workerA, Mock\DomainDockerServiceWorkerA::class));
        $this->assertTrue(is_a($workerB, Mock\DomainDockerServiceWorkerB::class));
    }
}
