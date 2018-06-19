<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;

class ServiceType
{
    /** @var Repository\ServiceType */
    protected $repo;

    public function __construct(Repository\ServiceType $repo)
    {
        $this->repo = $repo;
    }

    public function getBySlug(string $slug) : ?Entity\ServiceType
    {
        return $this->repo->findBySlug($slug);
    }

    /**
     * @param array $slugs
     * @return Entity\ServiceType[]
     */
    public function getAllBySlugs(array $slugs) : array
    {
        return $this->repo->findAllBySlugs($slugs);
    }
}
