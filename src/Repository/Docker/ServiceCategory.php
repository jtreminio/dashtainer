<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;

class ServiceCategory extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\ServiceCategory::class;

    /**
     * @return Entity\ServiceCategory[]
     */
    public function getAll() : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sc')
            ->addSelect('st')
            ->from('Dashtainer:Docker\ServiceCategory', 'sc')
            ->join('sc.types', 'st');

        return $qb->getQuery()->getResult();
    }

    /**
     * @var Entity\Project $project
     * @return Entity\ServiceCategory[]
     */
    public function findPublicServices(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sc')
            ->addSelect('st')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceCategory', 'sc')
            ->join('sc.types', 'st')
            ->join('st.services', 's')
            ->andWhere('s.project = :project')
            ->andWhere('st.is_public <> 0')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }
}
