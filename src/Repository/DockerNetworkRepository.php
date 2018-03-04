<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class DockerNetworkRepository implements ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\DockerNetwork::class;

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
     * @return Entity\DockerNetwork|null
     */
    public function find($id) : ?Entity\DockerNetwork
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\DockerNetwork[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\DockerNetwork[]
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
     * @return Entity\DockerNetwork|null
     */
    public function findOneBy(array $criteria) : ?Entity\DockerNetwork
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

    public function getPrimaryPublicNetwork(
        Entity\DockerProject $project
    ) : ?Entity\DockerNetwork {
        return $this->findOneBy([
            'project'           => $project,
            'is_primary_public' => true,
        ]);
    }

    public function getPrimaryPrivateNetwork(
        Entity\DockerProject $project
    ) : ?Entity\DockerNetwork {
        return $this->findOneBy([
            'project'            => $project,
            'is_primary_private' => true,
        ]);
    }
}
