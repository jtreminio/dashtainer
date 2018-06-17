<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;

class Network extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\Network::class;

    /**
     * @param Entity\Project $project
     * @param array          $names
     * @return Entity\Network[]
     */
    public function findByNames(Entity\Project $project, array $names) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from('Dashtainer:Docker\Network', 'n')
            ->andWhere('n.project = :project')
            ->andWhere('n.name IN (:names)')
            ->setParameters([
                'project' => $project,
                'names'   => $names,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Network[]
     */
    public function findByProject(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from('Dashtainer:Docker\Network', 'n')
            ->andWhere('n.project = :project')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Service $service
     * @return Entity\Network[]
     */
    public function findByService(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->addSelect('s')
            ->from('Dashtainer:Docker\Network', 'n')
            ->join('n.services', 's')
            ->andWhere(':service MEMBER OF n.services')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Service $service
     * @return Entity\Network[]
     */
    public function findByNotService(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->addSelect('s')
            ->from('Dashtainer:Docker\Network', 'n')
            ->join('n.services', 's')
            ->andWhere(':service NOT MEMBER OF n.services')
            ->andWhere('n.project = :project')
            ->setParameters([
                'service' => $service,
                'project' => $service->getProject(),
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Network[]
     */
    public function findWithNoServices(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from('Dashtainer:Docker\Network', 'n')
            ->leftJoin('n.services', 's')
            ->andWhere('n.project = :project')
            ->andWhere('s IS NULL')
            ->andWhere('n.is_editable = true')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }
}
