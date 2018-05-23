<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity;
use Dashtainer\Repository\Docker\Secret;

class RepoDockerSecret extends Secret
{
    public function findAllByProject(Entity\Docker\Project $project) : array
    {
        return $project->getSecrets()->toArray();
    }

    public function findAllByService(Entity\Docker\Service $service) : array
    {
        return $service->getSecrets()->toArray();
    }

    public function findOwned(Entity\Docker\Service $service) : array
    {
        $secrets = [];
        foreach ($service->getSecrets() as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            if ($projectSecret->getOwner() === $service) {
                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }

    public function findInternal(Entity\Docker\Service $service) : array
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            if ($serviceSecret->getIsInternal()) {
                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }

    public function findNotInternal(Entity\Docker\Service $service) : array
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            if (!$serviceSecret->getIsInternal()) {
                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }

    public function findGranted(Entity\Docker\Service $service) : array
    {
        $secrets = [];
        foreach ($service->getSecrets() as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            if ($projectSecret->getOwner() !== $service) {
                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }

    public function findNotGranted(
        Entity\Docker\Project $project,
        Entity\Docker\Service $service
    ) : array {
        $serviceSecrets = $service->getSecrets();

        $secrets = [];
        foreach ($project->getSecrets() as $projectSecret) {
            if (!$serviceSecrets->contains($projectSecret)) {
                $secrets []= $projectSecret;
            }
        }

        return $secrets;
    }

    public function deleteGrantedNotOwned(Entity\Docker\Service $service)
    {
        foreach ($service->getSecrets() as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            if ($projectSecret->getOwner() !== $service) {
                $service->removeSecret($serviceSecret);
            }
        }
    }
}
