<?php

namespace DashtainerBundle\Repository;

use DashtainerBundle\Entity;

use Doctrine\ORM;

class DockerServiceRepository extends ORM\EntityRepository
{
    /**
     * @param array      $criteria
     * @param array|null $orderBy
     * @return null|Entity\DockerService
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return parent::findOneBy($criteria, $orderBy);
    }
}
