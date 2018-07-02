<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\ServiceCategory;
use Dashtainer\Tests\Mock;

class ServiceCategoryTest extends DomainAbstract
{
    /** @var ServiceCategory */
    protected $serviceCategory;

    protected function setUp()
    {
    }

    public function testGetPublicServices()
    {
        $project = $this->createProject('project');

        $serviceCategoryA = $this->createServiceCategory('service-category-a');

        $serviceTypeA     = $this->createServiceType('service-type-a')
            ->setCategory($serviceCategoryA)
            ->setIsPublic(true);
        $serviceTypeB     = $this->createServiceType('service-type-b')
            ->setCategory($serviceCategoryA)
            ->setIsPublic(false);

        $serviceA = $this->createService('service-a')
            ->setType($serviceTypeA)
            ->setProject($project);
        $serviceB = $this->createService('service-b')
            ->setType($serviceTypeB)
            ->setProject($project);

        $serviceCategoryB = $this->createServiceCategory('service-category-b');

        $serviceTypeC     = $this->createServiceType('service-type-c')
            ->setCategory($serviceCategoryB)
            ->setIsPublic(true);
        $serviceTypeD     = $this->createServiceType('service-type-d')
            ->setCategory($serviceCategoryB)
            ->setIsPublic(false);

        $serviceC = $this->createService('service-c')
            ->setType($serviceTypeC)
            ->setProject($project);
        $serviceD = $this->createService('service-d')
            ->setType($serviceTypeD)
            ->setProject($project);

        $repo = new Mock\RepoDockerServiceCategory($this->getEm());
        $repo->setCategories([$serviceCategoryA, $serviceCategoryB]);

        $this->serviceCategory = new ServiceCategory($repo);

        $result = $this->serviceCategory->getPublicServices($project);

        $this->assertSame($serviceA, array_pop($result['service-category-a']));
        $this->assertSame($serviceC, array_pop($result['service-category-b']));
    }
}
