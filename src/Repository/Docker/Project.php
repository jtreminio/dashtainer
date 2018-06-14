<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\ORM\Query\Expr;
use Doctrine\Common\Persistence;

class Project implements Repository\ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\Docker\Project::class;

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
     * @return Entity\Docker\Project|null
     */
    public function find($id) : ?Entity\Docker\Project
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Project[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Project[]
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
     * @return Entity\Docker\Project|null
     */
    public function findOneBy(array $criteria) : ?Entity\Docker\Project
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
    ) : ?Entity\Docker\Project {
        return $this->findOneBy([
            'id'   => $id,
            'user' => $user
        ]);
    }

    /**
     * Return list of project ID, Name and count of Services in the Project
     *
     * @param Entity\User $user
     * @return array [id, name, service_count]
     */
    public function getNamesAndCount(Entity\User $user) : array
    {
        $query = '
            SELECT
                p.id id,
                p.name name,
                (
                    SELECT COUNT(*)
                    FROM docker_service ds
                    WHERE ds.project_id = p.id
                ) service_count
            FROM docker_project p
            WHERE p.user_id = :user
        ';

        $userId = $user->getId();

        $dbal = $this->em->getConnection();
        $stmt = $dbal->prepare($query);
        $stmt->bindParam(':user', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
