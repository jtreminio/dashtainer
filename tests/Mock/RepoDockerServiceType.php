<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity;
use Dashtainer\Repository\Docker\ServiceType;

class RepoDockerServiceType extends ServiceType
{
    /** @var Entity\Docker\ServiceType[] */
    private $serviceTypes = [];

    public function addServiceType(Entity\Docker\ServiceType $serviceType)
    {
        $this->serviceTypes []= $serviceType;
    }

    public function findBySlug(
        string $slug
    ): ?Entity\Docker\ServiceType {
        foreach ($this->serviceTypes as $serviceType) {
            if ($serviceType->getSlug() === $slug) {
                return $serviceType;
            }
        }

        return null;
    }

    public function findAllBySlugs(array $slugs) : array
    {
        $results = [];
        foreach ($this->serviceTypes as $serviceType) {
            if (!in_array($serviceType->getSlug(), $slugs)) {
                continue;
            }

            $results []= $serviceType;
        }

        return $results;
    }
}
