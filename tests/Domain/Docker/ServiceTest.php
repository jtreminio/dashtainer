<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Service;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Tests\Mock;

class ServiceTest extends DomainAbstract
{
    /** @var Service */
    protected $service;

    protected function setUp()
    {
        $this->service = new Service(new Mock\RepoDockerService($this->getEm()));
    }

    public function testGenerateNameReturnsServiceTypeNameOnNoExistingServicesOfType()
    {
        $project = $this->createProject('project');

        $serviceTypeA = $this->createServiceType('service-type-a');
        $serviceTypeB = $this->createServiceType('service-type-b');

        $serviceA = $this->createService('service-a');
        $serviceA->setType($serviceTypeA);

        $project->addService($serviceA);

        $version = null;

        $result = $this->service->generateName($project, $serviceTypeB, $version);

        $this->assertEquals('service-type-b', $result);
    }

    public function testGenerateNameReturnsServiceTypeNameWithVersionOnNoExistingServicesOfType()
    {
        $project = $this->createProject('project');

        $serviceTypeA = $this->createServiceType('service-type-a');
        $serviceTypeB = $this->createServiceType('service-type-b');

        $serviceA = $this->createService('service-a');
        $serviceA->setType($serviceTypeA);

        $project->addService($serviceA);

        $version = 1.2;

        $result = $this->service->generateName($project, $serviceTypeB, $version);

        $this->assertEquals('service-type-b-1-2', $result);
    }

    /**
     * @param string[] $serviceNames
     * @param string   $version
     * @param string   $expected
     * @dataProvider providerGenerateNameReturnsNameWithCountAppended
     */
    public function testGenerateNameReturnsNameWithCountAppended(
        array $serviceNames, string $version, string $expected
    ) {
        $project = $this->createProject('project');

        $serviceType = $this->createServiceType('service-type');

        foreach ($serviceNames as $serviceName) {
            $service = $this->createService($serviceName);
            $service->setVersion($version)
                ->setType($serviceType);

            $project->addService($service);
        }

        $result = $this->service->generateName($project, $serviceType, $version);

        $this->assertEquals($expected, $result);
    }

    public function providerGenerateNameReturnsNameWithCountAppended()
    {
        yield [
            ['service-type', 'service-type-1'], '', 'service-type-2'
        ];

        yield [
            ['service-type-1'], '', 'service-type-2'
        ];

        yield [
            ['service-type', 'service-type-1'], '7.2', 'service-type-7-2-1'
        ];

        yield [
            ['service-type-1'], '7.2', 'service-type-7-2-1'
        ];

        yield [
            [], '', 'service-type'
        ];

        yield [
            [], '7.2', 'service-type-7-2'
        ];
    }

    public function testGetUsedPublishedPorts()
    {
        $project = $this->createProject('project');

        $serviceType = $this->createServiceType('service-type');

        $serviceA = $this->createService('service-a');
        $portA    = $this->createPort('port-a', 1000, 2000);
        $serviceA->setType($serviceType)
            ->addPort($portA);

        $serviceB = $this->createService('service-b');
        $portB    = $this->createPort('port-b', 3000, 4000);
        $serviceB->setType($serviceType)
            ->addPort($portB);

        $serviceC = $this->createService('service-c');
        $portC    = $this->createPort('port-c', 5000, 6000);
        $serviceC->setType($serviceType)
            ->addPort($portC);

        $project->addService($serviceA)
            ->addService($serviceB)
            ->addService($serviceC);

        $result = $this->service->getUsedPublishedPorts($project, $serviceC);

        $expected = [
            'tcp' => [
                1000,
                3000,
            ],
            'udp' => [],
        ];

        $this->assertEquals($expected, $result);
    }
}
