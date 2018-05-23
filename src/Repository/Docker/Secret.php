<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\ORM\Query\Expr;
use Doctrine\Common\Persistence;

class Secret implements Repository\ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\Docker\Secret::class;

    /** @var ORM\EntityManagerInterface */
    protected $em;

    /** @var Persistence\ObjectRepository */
    protected $repo;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository(self::ENTITY_CLASS);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Secret|null
     */
    public function find($id) : ?Entity\Docker\Secret
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Secret[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Secret[]
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) : array {
        return $this->repo->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Secret|null
     */
    public function findOneBy(array $criteria) : ?Entity\Docker\Secret
    {
        return $this->repo->findOneBy($criteria);
    }

    public function save(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->persist($ent);
        }

        $this->em->flush();
    }

    public function delete(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->remove($ent);
        }

        $this->em->flush();
    }

    public function getClassName() : string
    {
        return self::ENTITY_CLASS;
    }

    /**
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Secret[]
     */
    public function findAllByProject(Entity\Docker\Project $project) : array
    {
        return $this->repo->findBy(['project' => $project]);
    }

    /**
     * Secrets owned by Service
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[]
     */
    public function findOwned(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('Dashtainer:Docker\Secret', 's', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('s.owner = :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets owned by Service and marked as internal
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[]
     */
    public function findInternal(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('Dashtainer:Docker\Secret', 's', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('s.owner = :service')
            ->andWhere('ss.service = :service')
            ->andWhere('ss.is_internal = 1')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets owned by Service and marked as not internal
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[]
     */
    public function findNotInternal(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('Dashtainer:Docker\Secret', 's', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('s.owner = :service')
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
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[]
     */
    public function findGranted(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('Dashtainer:Docker\Secret', 's', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('ss.service = :service')
            ->andWhere('s.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Secrets not granted to Service
     *
     * @param Entity\Docker\Project $project
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Secret[]
     */
    public function findNotGranted(
        Entity\Docker\Project $project,
        Entity\Docker\Service $service
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('s.id')
            ->from('Dashtainer:Docker\Secret', 's')
            ->join('Dashtainer:Docker\ServiceSecret', 'ss', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('ss.service = :service')
            ->andWhere('s.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        $granted = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $granted []= $item['id'];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Secret', 's')
            ->where('s.project = :project')
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

    /**
     * @param Entity\Docker\Service $service
     */
    public function deleteGrantedNotOwned(Entity\Docker\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ss')
            ->from('Dashtainer:Docker\ServiceSecret', 'ss')
            ->join('Dashtainer:Docker\Secret', 's', Expr\Join::WITH, 'ss.project_secret = s')
            ->where('ss.service = :service')
            ->andWhere('s.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        foreach ($qb->getQuery()->getResult() as $item) {
            $this->em->remove($item);
        }

        $this->em->flush();
    }
}
