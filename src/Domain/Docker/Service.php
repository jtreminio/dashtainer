<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;
use Dashtainer\Util;

class Service
{
    /** @var Repository\Service */
    protected $repo;

    public function __construct(Repository\Service $repo)
    {
        $this->repo    = $repo;
    }

    public function generateName(
        Entity\Project $project,
        Entity\ServiceType $serviceType,
        string $version = null
    ) : string {
        $services = $this->repo->findBy([
            'project' => $project,
            'type'    => $serviceType,
        ]);

        $version  = $version ? "-{$version}" : '';
        $hostname = Util\Strings::hostname("{$serviceType->getSlug()}{$version}");

        if (empty($services)) {
            return $hostname;
        }

        $usedNames = [];
        foreach ($services as $service) {
            $usedNames []= $service->getSlug();
        }

        for ($i = 1; $i < count($usedNames) + 2; $i++) {
            $name = "{$hostname}-{$i}";

            if (!in_array($name, $usedNames)) {
                return $name;
            }
        }

        return "{$hostname}-" . uniqid();
    }

    public function getUsedPublishedPorts(
        Entity\Project $project,
        Entity\Service $excludeService = null
    ) : array {
        $ports = [
            'tcp' => [],
            'udp' => [],
        ];

        foreach ($this->repo->getProjectPorts($project, $excludeService) as $port) {
            $ports [$port->getProtocol()] []= $port->getPublished();
        }

        return $ports;
    }
}
