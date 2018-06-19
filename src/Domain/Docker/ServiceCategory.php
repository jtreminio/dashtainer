<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;

class ServiceCategory
{
    /** @var Repository\ServiceCategory */
    protected $repo;

    public function __construct(Repository\ServiceCategory $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @return Entity\ServiceCategory[]
     */
    public function getAll() : array
    {
        return $this->repo->getAll();
    }

    public function getPublicServices(Entity\Project $project) : array
    {
        $servicesCategorized = [];

        foreach ($this->getAll() as $category) {
            $servicesCategorized[$category->getName()] = [];
        }

        foreach ($this->repo->findPublicServices($project) as $category) {
            foreach ($category->getTypes() as $type) {
                foreach ($type->getServices() as $service) {
                    $servicesCategorized[$category->getName()] []= $service;
                }
            }
        }

        return $servicesCategorized;
    }
}
