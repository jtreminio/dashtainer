<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;

class Secret extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\Secret::class;

    /**
     * All ServiceSecrets from all Services in Project
     *
     * @param Entity\Project $project
     * @return Entity\ServiceSecret[]
     */
    public function findAllServiceSecretsByProject(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('s.project = :project')
            ->andWhere('ss.service = s.owner')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * All ProjectSecrets by IDs
     *
     * @param Entity\Project $project
     * @param array          $ids
     * @return Entity\Secret[]
     */
    public function findByIds(Entity\Project $project, array $ids) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Secret', 's')
            ->andWhere('s.id IN (:ids)')
            ->andWhere('s.project = :project')
            ->setParameters([
                'project' => $project,
                'ids'     => $ids,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * All ServiceSecrets by name
     *
     * @param Entity\Service $service
     * @param array          $names
     * @return Entity\ServiceSecret[]
     */
    public function findByName(
        Entity\Service $service,
        array $names
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('ss.service = :service')
            ->andWhere('ss.name IN (:names)')
            ->setParameters([
                'service' => $service,
                'names'   => $names,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * All ProjectSecrets owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\Secret[]
     */
    public function findOwnedProjectSecrets(Entity\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->addSelect('ss')
            ->from('Dashtainer:Docker\Secret', 's')
            ->join('s.service_secrets', 'ss')
            ->andWhere('s.owner = :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * ServiceSecrets without ProjectSecrets
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[]
     */
    public function findOwnedServiceSecrets(Entity\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->andWhere('ss.service = :service')
            ->andWhere('ss.project_secret IS NULL')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets owned by Service and marked as internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[]
     */
    public function findInternal(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('s.owner = :service')
            ->andWhere('ss.service = :service')
            ->andWhere('ss.is_internal <> 0')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets owned by Service and marked as not internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[]
     */
    public function findNotInternal(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('s.owner = :service')
            ->andWhere('ss.service = :service')
            ->andWhere('ss.is_internal = 0')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets granted to but not owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[]
     */
    public function findGranted(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('ss.service = :service')
            ->andWhere('s.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets not granted to Service
     *
     * @param Entity\Project $project
     * @param Entity\Service $service
     * @return Entity\Secret[]
     */
    public function findNotGranted(
        Entity\Project $project,
        Entity\Service $service
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('s.id')
            ->from('Dashtainer:Docker\Secret', 's')
            ->join('s.service_secrets', 'ss')
            ->andWhere('ss.service = :service')
            ->andWhere('s.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        $granted = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $granted []= $item['id'];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->addSelect('s')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('ss.project_secret', 's')
            ->andWhere('s.project = :project')
            ->andWhere('s.owner <> :service');

        $parameters = [
            'project' => $project,
            'service' => $service,
        ];

        if ($granted) {
            $qb->andWhere('s.id NOT IN (:granted)');

            $parameters = [
                'project' => $project,
                'service' => $service,
                'granted' => $granted,
            ];
        }

        $qb->setParameters($parameters);

        $notGranted = $qb->getQuery()->getResult();

        return $notGranted;
    }
}
