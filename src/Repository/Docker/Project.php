<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Entity\User;
use Dashtainer\Repository;

class Project extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\Project::class;

    public function findByUserAndId(User $user, string $projectId) : ?Entity\Project
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('Dashtainer:Docker\Project', 'p')
            ->join('p.user', 'u')
            ->andWhere('p.id = :projectId')
            ->andWhere('u = :user')
            ->setParameters([
                'projectId' => $projectId,
                'user'      => $user,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByUserAndName(User $user, string $projectName) : ?Entity\Project
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('Dashtainer:Docker\Project', 'p')
            ->join('p.user', 'u')
            ->andWhere('p.name = :projectName')
            ->andWhere('u = :user')
            ->setParameters([
                'projectName' => $projectName,
                'user'        => $user,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Return list of project ID, Name and count of Services in the Project
     *
     * @param User $user
     * @return array [id, name, service_count]
     */
    public function getNamesAndCount(User $user) : array
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
