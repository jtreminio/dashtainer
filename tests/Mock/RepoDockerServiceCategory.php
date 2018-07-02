<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker\ServiceCategory;

class RepoDockerServiceCategory extends ServiceCategory
{
    /** @var Entity\ServiceCategory[] */
    protected $categories;

    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    public function getAll() : array
    {
        return $this->categories;
    }

    public function findPublicServices(Entity\Project $project) : array
    {
        foreach ($this->categories as $category) {
            foreach ($category->getTypes() as $type) {
                if (!$type->getIsPublic()) {
                    $category->removeType($type);

                    continue;
                }
            }
        }

        return $this->categories;
    }
}
