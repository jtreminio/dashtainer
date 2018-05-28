<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity;
use Dashtainer\Repository\Docker\Network;

class RepoDockerNetwork extends Network
{
    public function findAllByProject(
        Entity\Docker\Project $project
    ): array {
        return $project->getNetworks()->toArray();
    }

    public function findByProject(
        Entity\Docker\Project $project,
        string $id
    ) : ?Entity\Docker\Network {
        foreach ($project->getNetworks() as $network) {
            if ($network->getId() === $id) {
                return $network;
            }
        }

        return null;
    }

    public function findByProjectMultipleIds(
        Entity\Docker\Project $project,
        array $ids
    ) : array {
        $networks = [];
        foreach ($project->getNetworks() as $network) {
            if (in_array($network->getId(), $ids)){
                $networks []= $network;
            }
        }

        return $networks;
    }

    public function findByService(Entity\Docker\Service $service) : array
    {
        return $service->getNetworks()->toArray();
    }

    public function findByNotService(Entity\Docker\Service $service) : array
    {
        $project = $service->getProject();

        $networks = [];
        foreach ($project->getNetworks() as $network) {
            $networks [$network->getId()]= $network;
        }

        foreach ($service->getNetworks() as $network) {
            unset($networks[$network->getId()]);
        }

        return $networks;
    }

    public function findWithNoServices(Entity\Docker\Project $project) : array
    {
        $networks = [];
        foreach ($project->getNetworks() as $network) {
            if (!$network->getServices()->count()) {
                $networks []= $network;
            }
        }

        return $networks;
    }

    public function getPublicNetwork(
        Entity\Docker\Project $project
    ) : ?Entity\Docker\Network {
        foreach ($project->getNetworks() as $network) {
            if ($network->getIsPublic()) {
                return $network;
            }
        }

        return null;
    }

    public function getPrivateNetworks(
        Entity\Docker\Project $project
    ) : array {
        $networks = [];
        foreach ($project->getNetworks() as $network) {
            if (!$network->getIsPublic()) {
                $networks []= $network;
            }
        }

        return $networks;
    }
}
