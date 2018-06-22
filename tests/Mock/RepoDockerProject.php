<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Entity\User;
use Dashtainer\Repository\Docker\Project;

class RepoDockerProject extends Project
{
    public function findByUserAndId(User $user, string $projectId) : ?Entity\Project
    {
        // ->andWhere('u = :user')
        foreach ($user->getProjects() as $project) {
            // ->andWhere('p.id = :projectId')
            if ($project->getId() === $projectId) {
                return $project;
            }
        }

        return null;
    }

    public function findByUserAndName(User $user, string $projectName) : ?Entity\Project
    {
        // ->andWhere('u = :user')
        foreach ($user->getProjects() as $project) {
            // ->andWhere('p.name = :projectName')
            if ($project->getName() === $projectName) {
                return $project;
            }
        }

        return null;
    }

    public function getNamesAndCount(User $user) : array
    {
        $data = [];

        foreach ($user->getProjects() as $project) {
            $data []= [
                'id'            => $project->getId(),
                'name'          => $project->getName(),
                'service_count' => $project->getServices()->count(),
            ];
        }

        return $data;
    }
}
