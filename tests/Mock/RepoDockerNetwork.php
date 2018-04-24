<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity;
use Dashtainer\Repository\Docker\Network;

class RepoDockerNetwork extends Network
{
    public function getPublicNetwork(
        Entity\Docker\Project $project
    ): ?Entity\Docker\Network {
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
            if ($network->getIsPublic()) {
                continue;
            }

            $networks []= $network;
        }

        return $networks;
    }

    public function findByService(Entity\Docker\Service $service, bool $public = false) : array
    {
        $networks = [];

        foreach ($service->getNetworks() as $network) {
            if ($network->getIsPublic() && !$public) {
                continue;
            }

            $networks []= $network;
        }

        return $networks;
    }
}
