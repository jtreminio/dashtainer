<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

class Network
{
    /** @var Repository\Docker\Network */
    protected $repo;

    protected $wordsListFile;

    public function __construct(
        Repository\Docker\Network $repo,
        string $wordListFile
    ) {
        $this->repo = $repo;

        $this->wordsListFile = $wordListFile;
    }

    /**
     * Deletes Network from Project.
     *
     * Removes association between Services and Network.
     *
     * @param Entity\Docker\Network $network
     */
    public function delete(Entity\Docker\Network $network)
    {
        $services = [];
        foreach ($network->getServices() as $service) {
            $network->removeService($service);
            $service->removeNetwork($network);

            $services []= $service;
        }

        $this->repo->save($network, ...$services);
        $this->repo->delete($network);
    }

    /**
     * Picks random string from word list file for a Network name.
     *
     * Does a diff between existing Network names and possible results.
     *
     * @param Entity\Docker\Project $project
     * @return string
     */
    public function generateName(Entity\Docker\Project $project) : string
    {
        $existingNetworks = $this->repo->findAllByProject($project);

        $existingNames = [];
        foreach ($existingNetworks as $network) {
            $existingNames []= $network->getName();
        }

        $file = file($this->wordsListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $diff = array_diff($file, $existingNames);

        return trim($diff[array_rand($diff)]);
    }

    public function addToPublicNetwork(Entity\Docker\Service $service)
    {
        $publicNetwork = $this->repo->getPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($publicNetwork);
    }

    public function getPublicNetwork(
        Entity\Docker\Project $project
    ) : ?Entity\Docker\Network {
        return $this->repo->getPublicNetwork($project);
    }

    /**
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Network[]
     */
    public function getPrivateNetworks(Entity\Docker\Project $project) : array
    {
        return $this->repo->getPrivateNetworks($project);
    }

    /**
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Network[]
     */
    public function findByService(Entity\Docker\Service $service) : array
    {
        return $this->repo->findByService($service);
    }

    /**
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Network[]
     */
    public function findByNotService(Entity\Docker\Service $service) : array
    {
        return $this->repo->findByNotService($service);
    }

    /**
     * IN TEST VERSION SET ID!
     *
     * Create new Networks and assign Service to it
     *
     * @param Entity\Docker\Service $service
     * @param string[]              $networkNames
     */
    public function createNetworksForService(Entity\Docker\Service $service, array $networkNames)
    {
        if (empty($networkNames)) {
            return;
        }

        $project = $service->getProject();

        $created = [];

        foreach ($networkNames as $networkName) {
            $network = new Entity\Docker\Network();
            $network->setName($networkName)
                ->setProject($project)
                ->setIsEditable(true)
                ->addService($service);

            $service->addNetwork($network);

            $created []= $network;
        }

        $this->repo->save($project, $service, ...$created);
    }

    /**
     * Clears Networks from Service, then adds Service to Networks by ID
     *
     * @param Entity\Docker\Service $service
     * @param string[]              $networkIds
     */
    public function joinNetworks(Entity\Docker\Service $service, array $networkIds)
    {
        $project = $service->getProject();

        // Clear all existing Networks from Service
        $saved = [];
        foreach ($this->repo->findByService($service) as $network) {
            if ($network->getIsPublic()) {
                continue;
            }

            $network->removeService($service);
            $service->removeNetwork($network);

            $saved []= $network;
        }

        foreach ($this->repo->findByProjectMultipleIds($project, $networkIds) as $network) {
            $network->addService($service);
            $service->addNetwork($network);

            $saved []= $network;
        }

        $this->repo->save($service, ...$saved);
    }

    /**
     * Delete Networks that have no Services attached to them
     *
     * @param Entity\Docker\Project $project
     */
    public function deleteEmptyNetworks(Entity\Docker\Project $project)
    {
        $networks = $this->repo->findWithNoServices($project);

        $this->repo->delete(...$networks);
    }
}
