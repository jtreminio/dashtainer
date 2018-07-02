<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker\Service;

class RepoDockerService extends Service
{
    public function findByProjectAndId(Entity\Project $project, string $id) : ?Entity\Service
    {
        foreach ($project->getServices() as $service) {
            // ->andWhere('s.id = :id')
            if ($service->getId() === $id) {
                return $service;
            }
        }

        return null;
    }

    public function findAllByProject(Entity\Project $project) : array
    {
        return $project->getServices()->toArray();
    }

    public function findAllPublicByProject(Entity\Project $project) : array
    {
        $services = [];

        foreach ($project->getServices() as $service) {
            $type = $service->getType();

            // ->andWhere('st.is_public <> 0')
            if ($type->getIsPublic()) {
                $services []= $service;
            }
        }

        return $services;
    }

    public function findByProjectAndName(Entity\Project $project, string $name) : ?Entity\Service
    {
        foreach ($project->getServices() as $service) {
            // ->andWhere('s.name = :name')
            if ($service->getName() === $name) {
                return $service;
            }
        }

        return null;
    }

    public function findByProjectAndType(Entity\Project $project, Entity\ServiceType $type) : array
    {
        $services = [];

        foreach ($project->getServices() as $service) {
            $serviceType = $service->getType();

            // ->andWhere('s.type = :type')
            if ($serviceType->getName() !== $type->getName()) {
                continue;
            }

            $services []= $service;
        }

        return $services;
    }

    public function findByProjectAndTypeName(Entity\Project $project, string $typeName) : array
    {
        $services = [];

        foreach ($project->getServices() as $service) {
            $serviceType = $service->getType();

            // ->andWhere('st.name = :typeName')
            if ($serviceType->getName() !== $typeName) {
                continue;
            }

            $services []= $service;
        }

        return $services;
    }

    public function findChildByType(
        Entity\Service $parent,
        Entity\ServiceType $childType
    ) : ?Entity\Service {
        // ->andWhere('s.parent = :parent')
        foreach ($parent->getChildren() as $child) {
            $serviceType = $child->getType();

            // ->andWhere('s.type = :type')
            if ($serviceType->getName() === $childType->getName()) {
                return $child;
            }
        }

        return null;
    }

    public function findChildByTypeName(
        Entity\Service $parent,
        string $typeName
    ) : ?Entity\Service {
        // ->andWhere('s.parent = :parent')
        foreach ($parent->getChildren() as $child) {
            $serviceType = $child->getType();

            // ->andWhere('st.name = :typeName')
            if ($serviceType->getName() === $typeName) {
                return $child;
            }
        }

        return null;
    }

    public function findByNotNetwork(Entity\Network $network) : array
    {
        $services = [];

        $project = $network->getProject();

        foreach ($project->getServices() as $service) {
            // ->andWhere(':network NOT MEMBER OF s.networks')
            if ($network->getServices()->contains($services)) {
                continue;
            }

            $services []= $service;
        }

        return $services;
    }

    public function getProjectPorts(
        Entity\Project $project,
        Entity\Service $excludeService = null
    ) : array {
        $ports = [];

        foreach ($project->getServices() as $service) {
            // $qb->andWhere('sp.service <> :service');
            if ($excludeService && $excludeService->getName() === $service->getName()) {
                continue;
            }

            foreach ($service->getPorts() as $port) {
                $ports []= $port;
            }
        }

        return $ports;
    }
}
