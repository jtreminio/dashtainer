<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker\Secret;

class RepoDockerSecret extends Secret
{
    public function findAllServiceSecretsByProject(Entity\Project $project) : array
    {
        $secrets = [];

        foreach ($project->getServices() as $service) {
            foreach ($service->getSecrets() as $serviceSecret) {
                // ->join('ss.project_secret', 's')
                if (!$projectSecret = $serviceSecret->getProjectSecret()) {
                    continue;
                }

                // ->andWhere('ss.service = s.owner')
                if ($projectSecret->getOwner() !== $service) {
                    continue;
                }

                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }

    public function findByIds(Entity\Project $project, array $ids) : array
    {
        $secrets = [];

        foreach ($project->getSecrets() as $secret) {
            // ->andWhere('s.id IN (:ids)')
            if (!in_array($secret->getId(), $ids)) {
                continue;
            }

            $secrets []= $secret;
        }

        return $secrets;
    }

    public function findByName(
        Entity\Service $service,
        array $names
    ) : array {
        $secrets = [];

        foreach ($service->getSecrets() as $secret) {
            // ->andWhere('ss.name IN (:names)')
            if (!in_array($secret->getName(), $names)) {
                continue;
            }

            $secrets []= $secret;
        }

        return $secrets;
    }

    public function findOwnedProjectSecrets(Entity\Service $service)
    {
        $project = $service->getProject();

        $secrets = [];

        foreach ($project->getSecrets() as $projectSecret) {
            if ($projectSecret->getOwner() !== $service) {
                continue;
            }

            $secrets []= $projectSecret;
        }

        return $secrets;
    }

    public function findOwnedServiceSecrets(Entity\Service $service)
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            if ($serviceSecret->getProjectSecret()) {
                continue;
            }

            $secrets []= $serviceSecret;
        }

        return $secrets;
    }

    public function findInternal(Entity\Service $service) : array
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            // ->join('ss.project_secret', 's')
            if (!$projectSecret = $serviceSecret->getProjectSecret()) {
                continue;
            }

            // ->andWhere('s.owner = :service')
            if ($projectSecret->getOwner() !== $service) {
                continue;
            }

            // ->andWhere('ss.is_internal <> 0')
            if (!$serviceSecret->getIsInternal()) {
                continue;
            }

            $secrets []= $serviceSecret;
        }

        return $secrets;
    }

    public function findNotInternal(Entity\Service $service) : array
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            // ->join('ss.project_secret', 's')
            if (!$projectSecret = $serviceSecret->getProjectSecret()) {
                continue;
            }

            // ->andWhere('s.owner = :service')
            if ($projectSecret->getOwner() !== $service) {
                continue;
            }

            // ->andWhere('ss.is_internal = 0')
            if ($serviceSecret->getIsInternal()) {
                continue;
            }

            $secrets []= $serviceSecret;
        }

        return $secrets;
    }

    public function findGranted(Entity\Service $service) : array
    {
        $secrets = [];

        foreach ($service->getSecrets() as $serviceSecret) {
            // ->join('ss.project_secret', 's')
            if (!$projectSecret = $serviceSecret->getProjectSecret()) {
                continue;
            }

            // ->andWhere('s.owner <> :service')
            if ($projectSecret->getOwner() === $service) {
                continue;
            }

            $secrets []= $serviceSecret;
        }

        return $secrets;
    }

    public function findNotGranted(
        Entity\Project $project,
        Entity\Service $service
    ) : array {
        $secrets = [];

        $granted = [];
        /** @var Entity\ServiceSecret $grantedServiceSecret */
        foreach ($this->findGranted($service) as $grantedServiceSecret) {
            $granted []= $grantedServiceSecret->getProjectSecret();
        }

        foreach ($project->getSecrets() as $projectSecret) {
            // ->join('s.service_secrets', 'ss')
            foreach ($projectSecret->getServiceSecrets() as $serviceSecret) {
                // ->andWhere('s.owner <> :service')
                if ($serviceSecret->getService() === $service) {
                    continue;
                }

                if (in_array($projectSecret, $granted)) {
                    continue;
                }

                $secrets []= $serviceSecret;
            }
        }

        return $secrets;
    }
}
