<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class DockerProjectRepository implements ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\DockerProject::class;

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
     * @return Entity\DockerProject|null
     */
    public function find($id) : ?Entity\DockerProject
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerProject[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\DockerProject[]
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
     * @return Entity\DockerProject|null
     */
    public function findOneBy(array $criteria) : ?Entity\DockerProject
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

    public function findByUser(
        Entity\User $user,
        string $id
    ) : ?Entity\DockerProject {
        return $this->findOneBy([
            'id'   => $id,
            'user' => $user
        ]);
    }
}
