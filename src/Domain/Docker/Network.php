<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;

use Doctrine\Common\Collections;

class Network
{
    /** @var Repository\Network */
    protected $repo;

    public function __construct(Repository\Network $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Returns Networks required for new Service
     *
     * @param Entity\Project $project
     * @param array          $internalNetworksArray
     * @return Collections\ArrayCollection[]
     */
    public function getForNewService(
        Entity\Project $project,
        array $internalNetworksArray
    ) : array {
        $joined   = new Collections\ArrayCollection();
        $unjoined = new Collections\ArrayCollection();

        // Always show private network on new Service
        $internalNetworksArray []= 'private';
        $internalNetworksArray = array_unique($internalNetworksArray);

        foreach ($this->repo->findByNames($project, $internalNetworksArray) as $network) {
            $joined->set($network->getName(), $network);
        }

        foreach ($this->repo->findByProject($project) as $network) {
            if ($joined->contains($network)) {
                continue;
            }

            $unjoined->set($network->getName(), $network);
        }

        return [
            'joined'   => $joined,
            'unjoined' => $unjoined,
        ];
    }

    /**
     * Returns Networks required for existing Service
     *
     * @param Entity\Service $service
     * @param array          $internalNetworksArray
     * @return Collections\ArrayCollection[]
     */
    public function getForExistingService(
        Entity\Service $service,
        array $internalNetworksArray
    ) : array {
        $project = $service->getProject();

        $joined   = new Collections\ArrayCollection();
        $unjoined = new Collections\ArrayCollection();

        foreach ($this->repo->findByNames($project, $internalNetworksArray) as $network) {
            $joined->set($network->getName(), $network);
        }

        foreach ($this->repo->findByService($service) as $network) {
            if ($joined->contains($network)) {
                continue;
            }

            $joined->set($network->getName(), $network);
        }

        foreach ($this->repo->findByProject($project) as $network) {
            if ($joined->contains($network)) {
                continue;
            }

            $unjoined->set($network->getName(), $network);
        }

        return [
            'joined'   => $joined,
            'unjoined' => $unjoined,
        ];
    }

    /**
     * @param Entity\Service $service
     * @return Entity\Network[]
     */
    public function findByService(Entity\Service $service) : array
    {
        return $this->repo->findByService($service);
    }

    /**
     * @param Entity\Service $service
     * @return Entity\Network[]
     */
    public function findByNotService(Entity\Service $service) : array
    {
        return $this->repo->findByNotService($service);
    }

    /**
     * @param Entity\Service $service
     * @param array          $internalNetworks Required networks for this Service
     * @param array          $configs          User-provided data from form
     */
    public function save(
        Entity\Service $service,
        array $internalNetworks,
        array $configs
    ) {
        $configs = $this->saveInternal($service, $internalNetworks, $configs);
        $this->saveNotInternal($service, $configs);
    }

    /**
     * Adds Service to internal Networks
     *
     * All Services will have 0 or more internal Networks they must join.
     *
     * @param Entity\Service   $service
     * @param Entity\Network[] $internalNetworks Required networks for this Service
     * @param array            $configs          User-provided data from form
     * @return array Array without internal Networks, containing only non-internal User Networks
     */
    protected function saveInternal(
        Entity\Service $service,
        array $internalNetworks,
        array $configs
    ) : array {
        foreach ($internalNetworks as $network) {
            $configs [$network->getId()]= [
                'id'   => $network->getId(),
                'name' => $network->getName(),
            ];
        }

        return $configs;
    }

    /**
     * Adds Service to specified Networks, creating non-existing Networks
     *
     * @param Entity\Service $service
     * @param array          $configs User-provided data from form
     */
    protected function saveNotInternal(
        Entity\Service $service,
        array $configs
    ) {
        $project = $service->getProject();

        $networks = [];
        foreach ($this->repo->findByProject($project) as $network) {
            $networks [$network->getId()] = $network;
        }

        foreach ($configs as $id => $data) {
            if (empty($data['id'])) {
                continue;
            }

            if (!array_key_exists($id, $networks)) {
                $network = new Entity\Network();
                $network->setName($data['name'])
                    ->setProject($project);

                $networks [$id]= $network;
            }

            /** @var Entity\Network $network */
            $network = $networks[$id];
            $network->addService($service);

            $this->repo->persist($network);
            unset($networks[$id]);
        }

        // No longer wanted by user
        foreach ($networks as $network) {
            $network->removeService($service);

            $this->repo->persist($network);
        }

        $this->repo->persist($service);
    }

    /**
     * Delete Networks that have no Services attached to them
     *
     * @param Entity\Project $project
     */
    public function deleteEmptyNetworks(Entity\Project $project)
    {
        foreach ($this->repo->findWithNoServices($project) as $network) {
            $project->removeNetwork($network);

            $this->repo->remove($network);
        }
    }
}
