<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\ORM\Query\Expr;
use Doctrine\Common\Persistence;

class Service implements Repository\ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\Docker\Service::class;

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
     * @return Entity\Docker\Service|null
     */
    public function find($id) : ?Entity\Docker\Service
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Service[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Service[]
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
     * @return Entity\Docker\Service|null
     */
    public function findOneBy(array $criteria) : ?Entity\Docker\Service
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

    public function findByProject(
        Entity\Docker\Project $project,
        string $id
    ) : ?Entity\Docker\Service {
        return $this->findOneBy([
            'id'      => $id,
            'project' => $project,
        ]);
    }

    /**
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Service[]
     */
    public function findAllByProject(
        Entity\Docker\Project $project
    ) : array {
        return $this->findBy([
            'project' => $project,
        ]);
    }

    /**
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Service[]
     */
    public function findAllPublicByProject(
        Entity\Docker\Project $project
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('Dashtainer:Docker\ServiceType', 'st', Expr\Join::WITH, 's.type = st')
            ->where('st.is_public != 0')
            ->andWhere('s.project = :project')
            ->andWhere('1 = 1')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Docker\Project     $project
     * @param Entity\Docker\ServiceType $type
     * @return Entity\Docker\Service[]
     */
    public function findByProjectAndType(
        Entity\Docker\Project $project,
        Entity\Docker\ServiceType $type
    ) : array {
        return $this->findBy([
            'project' => $project,
            'type'    => $type,
        ]);
    }

    public function findChildByType(
        Entity\Docker\Service $parent,
        Entity\Docker\ServiceType $childType
    ) : ?Entity\Docker\Service {
        return $this->findOneBy([
            'parent' => $parent,
            'type'   => $childType,
        ]);
    }

    /**
     * @param Entity\Docker\Network $network
     * @return Entity\Docker\Service[]
     */
    public function findByNotNetwork(Entity\Docker\Network $network) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->where(':network NOT MEMBER OF s.networks')
            ->setParameters(['network' => $network]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Docker\Project $project
     * @param Entity\Docker\Service $excludeService
     * @return Entity\Docker\ServicePort[]
     */
    public function getProjectPorts(
        Entity\Docker\Project $project,
        Entity\Docker\Service $excludeService = null
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('sp')
            ->from('Dashtainer:Docker\ServicePort', 'sp')
            ->join('sp.service', 's')
            ->join('s.project', 'p')
            ->andWhere('s.project = :project');

        $params = [
            'project' => $project,
        ];

        if ($excludeService) {
            $qb->andWhere('sp.service <> :service');

            $params = [
                'project' => $project,
                'service' => $excludeService,
            ];
        }

        $qb->setParameters($params);

        return $qb->getQuery()->getResult();
    }
}
