<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Service;
use Dashtainer\Domain\Docker\WorkerBag;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceTest extends KernelTestCase
{
    /** @var WorkerBag */
    protected $manager;

    /** @var Service */
    protected $service;

    /** @var MockObject|Repository\Docker\Service */
    protected $serviceRepo;

    protected function setUp()
    {
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->serviceRepo = $this->getMockBuilder(Repository\Docker\Service::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->manager = $this->getMockBuilder(WorkerBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new Service($this->serviceRepo, $this->manager);
    }

    public function testGenerateNameReturnsServiceTypeNameOnNoExistingServicesOfType()
    {
        $project     = new Entity\Docker\Project();
        $serviceType = new Entity\Docker\ServiceType();
        $serviceType->setSlug('service-type-slug');
        $version     = null;

        $services = [];

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with(['project' => $project, 'type' => $serviceType])
            ->will($this->returnValue($services));

        $result = $this->service->generateName($project, $serviceType, $version);

        $this->assertEquals('service-type-slug', $result);
    }

    public function testGenerateNameReturnsServiceTypeNameWithVersionOnNoExistingServicesOfType()
    {
        $project     = new Entity\Docker\Project();
        $serviceType = new Entity\Docker\ServiceType();
        $serviceType->setSlug('service-type-slug');
        $version     = 1.2;

        $services = [];

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with(['project' => $project, 'type' => $serviceType])
            ->will($this->returnValue($services));

        $result = $this->service->generateName($project, $serviceType, $version);

        $this->assertEquals('service-type-slug-1-2', $result);
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
        $project     = new Entity\Docker\Project();
        $serviceType = new Entity\Docker\ServiceType();
        $serviceType->setSlug('service-name');

        $services = [];
        foreach ($serviceNames as $serviceName) {
            $services []= (new Entity\Docker\Service())->setName($serviceName);
        }

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with(['project' => $project, 'type' => $serviceType])
            ->will($this->returnValue($services));

        $result = $this->service->generateName($project, $serviceType, $version);

        $this->assertEquals($expected, $result);
    }

    public function providerGenerateNameReturnsNameWithCountAppended()
    {
        yield [
            ['service-name', 'service-name-1'], '', 'service-name-2'
        ];

        yield [
            ['service-name-1'], '', 'service-name-2'
        ];

        yield [
            ['service-name', 'service-name-1'], '7.2', 'service-name-7-2-1'
        ];

        yield [
            ['service-name-1'], '7.2', 'service-name-7-2-1'
        ];

        yield [
            [], '', 'service-name'
        ];

        yield [
            [], '7.2', 'service-name-7-2'
        ];
    }

    public function testValidateByNameReturnsNoDiffWhenAllServiceNamesExist()
    {
        $project = new Entity\Docker\Project();

        $serviceA = new Entity\Docker\Service();
        $serviceA->setName('serviceA');

        $serviceB = new Entity\Docker\Service();
        $serviceB->setName('serviceB');

        $services = [$serviceA, $serviceB];

        $servicesList = ['serviceA', 'serviceB'];

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with([
                    'project' => $project,
                    'name'    => $servicesList,
                ])
            ->will($this->returnValue($services));

        $result = $this->service->validateByName($project, $servicesList);

        $this->assertEmpty($result);
    }

    public function testValidateByNameReturnsDiffWhenAServiceNameDoesNotExist()
    {
        $project = new Entity\Docker\Project();

        $serviceA = new Entity\Docker\Service();
        $serviceA->setName('serviceA');

        $services = [$serviceA];

        $servicesList = ['serviceA', 'serviceB', 'serviceC'];

        $this->serviceRepo->expects($this->once())
            ->method('findBy')
            ->with([
                    'project' => $project,
                    'name'    => $servicesList,
                ])
            ->will($this->returnValue($services));

        $result = $this->service->validateByName($project, $servicesList);

        $expected = ['serviceB', 'serviceC'];

        $this->assertEquals(array_values($expected), array_values($result));
    }
}
