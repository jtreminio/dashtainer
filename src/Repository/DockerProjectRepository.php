<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class DockerProjectRepository implements Persistence\ObjectRepository
{
    protected const ENTITY_CLASS = Entity\DockerProject::class;

    /** @var ORM\EntityManagerInterface */
    protected $repository;

    public function __construct(ORM\EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(self::ENTITY_CLASS);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerProject|null
     */
    public function find($id) : ?Entity\DockerProject
    {
        return $this->repository->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerProject[]
     */
    public function findAll() : array
    {
        return $this->repository->findAll();
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
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerProject|null
     */
    public function findOneBy(array $criteria) : ?Entity\DockerProject
    {
        return $this->repository->findOneBy($criteria);
    }

    public function getClassName() : string
    {
        return self::ENTITY_CLASS;
    }
}
