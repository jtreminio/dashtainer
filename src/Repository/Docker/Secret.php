<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
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

    public function findByProject(
        Entity\Docker\Project $project,
        string $id
    ) : ?Entity\Docker\Secret {
        return $this->findOneBy([
            'id'      => $id,
            'project' => $project,
        ]);
    }

    /**
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Secret[]
     */
    public function findByService(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Secret', 's')
            ->where(':service MEMBER OF s.services')
            ->andWhere('s.project = :project');

        $qb->setParameters([
            'service' => $service,
            'project' => $service->getProject(),
        ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Secret[]
     */
    public function findByNotService(Entity\Docker\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Secret', 's')
            ->where(':service NOT MEMBER OF s.services')
            ->andWhere('s.project = :project');

        $qb->setParameters([
            'service' => $service,
            'project' => $service->getProject(),
        ]);

        return $qb->getQuery()->getResult();
    }
}
