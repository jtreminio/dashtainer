<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class DockerServiceTypeRepository implements ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\DockerServiceType::class;

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
     * @return Entity\DockerServiceType|null
     */
    public function find($id) : ?Entity\DockerServiceType
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerServiceType[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\DockerServiceType[]
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
     * @return Entity\DockerServiceType|null
     */
    public function findOneBy(array $criteria) : ?Entity\DockerServiceType
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

    public function findBySlug(
        string $slug
    ) : ?Entity\DockerServiceType {
        return $this->findOneBy([
            'slug' => $slug,
        ]);
}
