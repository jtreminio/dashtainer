<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;

class ServiceType extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\ServiceType::class;

    public function findBySlug(string $slug) : ?Entity\ServiceType
    {
        $qb = $this->em->createQueryBuilder()
            ->select('st')
            ->from('Dashtainer:Docker\ServiceType', 'st')
            ->andWhere('st.slug = :slug')
            ->setParameters([
                'slug' => $slug,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $slugs
     * @return Entity\ServiceType[]
     */
    public function findAllBySlugs(array $slugs) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('st')
            ->addSelect('st')
            ->from('Dashtainer:Docker\ServiceType', 'st')
            ->join('st.meta', 'm')
            ->andWhere('st.slug IN (:slug)')
            ->setParameters([
                'slug' => $slugs,
            ]);

        return $qb->getQuery()->getResult();
    }
}
