<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity;
use Dashtainer\Repository\Docker\Service;

class RepoDockerService extends Service
{
    public function findByProjectAndType(
        Entity\Docker\Project $project,
        Entity\Docker\ServiceType $type
    ): array {
        $services = [];

        foreach ($project->getServices() as $service) {
            $serviceType = $service->getType();

            if ($serviceType->getName() == $type->getName()) {
                $services []= $service;
            }
        }

        return $services;
    }

    public function findChildByType(
        Entity\Docker\Service $parent,
        Entity\Docker\ServiceType $childType
    ): ?Entity\Docker\Service {
        foreach ($parent->getChildren() as $service) {
            $serviceType = $service->getType();

            if ($serviceType->getName() == $childType->getName()) {
                return $service;
            }
        }

        return null;
    }
}
