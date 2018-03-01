<?php

namespace DashtainerBundle\Repository;

use DashtainerBundle\Entity;

use Doctrine\ORM;

class ServiceRepository extends ORM\EntityRepository
{
    /**
     * @param array      $criteria
     * @param array|null $orderBy
     * @return null|Entity\Service
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return parent::findOneBy($criteria, $orderBy);
    }
}
