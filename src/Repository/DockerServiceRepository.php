<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class DockerServiceRepository implements ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\DockerService::class;

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
     * @return Entity\DockerService|null
     */
    public function find($id) : ?Entity\DockerService
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerService[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\DockerService[]
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
     * @return Entity\DockerService|null
     */
    public function findOneBy(array $criteria) : ?Entity\DockerService
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
        Entity\DockerProject $project,
        string $id
    ) : ?Entity\DockerService {
        return $this->findOneBy([
            'id'      => $id,
            'project' => $project,
        ]);
    }
}
