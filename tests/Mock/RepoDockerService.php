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

    public function getProjectBindPorts(Entity\Docker\Project $project) : array
    {
        $ports = [];

        foreach ($project->getServices() as $service) {
            foreach ($service->getMetas() as $meta) {
                if ($meta->getName() !== 'bind-port') {
                    continue;
                }

                $ports []= $meta->getData();
            }
        }

        return $ports;
    }
}
