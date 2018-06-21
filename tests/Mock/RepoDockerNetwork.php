<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker\Network;

class RepoDockerNetwork extends Network
{
    public function findByNames(Entity\Project $project, array $names) : array
    {
        $networks = [];

        foreach ($project->getNetworks() as $network) {
            // ->andWhere('n.name IN (:names)')
            if (!in_array($network->getName(), $names)) {
                continue;
            }

            $networks []= $network;
        }

        return $networks;
    }

    public function findByProject(Entity\Project $project) : array
    {
        return $project->getNetworks()->toArray();
    }

    public function findByService(Entity\Service $service) : array
    {
        return $service->getNetworks()->toArray();
    }

    public function findByNotService(Entity\Service $service) : array
    {
        $networks = [];

        $project = $service->getProject();

        foreach ($project->getNetworks() as $network) {
            // ->andWhere(':service NOT MEMBER OF n.services')
            if ($service->getNetworks()->contains($network)) {
                continue;
            }

            $networks []= $network;
        }

        return $networks;
    }

    public function findWithNoServices(Entity\Project $project) : array
    {
        $networks = [];

        foreach ($project->getNetworks() as $network) {
            // ->andWhere('s IS NULL')
            if (!$network->getServices()->count()) {
                continue;
            }

            // ->andWhere('n.is_editable = true')
            if (!$network->getIsEditable()) {
                continue;
            }

            $networks []= $network;
        }

        return $networks;
    }
}
