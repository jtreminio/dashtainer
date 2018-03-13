<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
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
}
