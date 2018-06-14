<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;

class Secret
{
    /** @var Repository\Secret */
    protected $repo;

    public function __construct(Repository\Secret $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Deletes all Secrets owned or assigned to Service
     *
     * @param Entity\Service $service
     */
    public function deleteAllForService(Entity\Service $service)
    {
        $this->repo->deleteSecrets($service);
        $this->repo->deleteServiceSecrets($service);
        $this->repo->deleteGrantedNotOwned($service);
    }

    /**
     * Returns array of IDs that do not belong to Project
     *
     * @param Entity\Project $project
     * @param int[]          $ids
     * @return int[]
     */
    public function idsNotBelongToProject(
        Entity\Project $project,
        array $ids
    ) : array {
        if (empty($ids)) {
            return [];
        }

        $secrets = [];
        foreach ($this->repo->findAllByProject($project) as $secret) {
            $secrets []= $secret->getId();
        }

        return array_diff($ids, $secrets);
    }

    /**
     * Returns array of IDs that do not belong to Service
     *
     * @param Entity\Service $service
     * @param int[]          $ids
     * @return int[]
     */
    public function idsNotBelongToService(
        Entity\Service $service,
        array $ids
    ) : array {
        if (empty($ids)) {
            return [];
        }

        $secrets = [];
        foreach ($this->repo->findOwned($service) as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            $secrets []= $projectSecret->getId();
        }

        return array_diff($ids, $secrets);
    }

    /**
     * All Project Secrets belonging to Project
     *
     * @param Entity\Project $project
     * @return Entity\Secret[] Keyed by Entity\Secret.name
     */
    public function getAll(Entity\Project $project) : array
    {
        return $this->sortProjectSecrets($this->repo->findAllByProject($project));
    }

    /**
     * All internal and not-internal ServiceSecrets owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getOwned(Entity\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findOwned($service));
    }

    /**
     * Internal Secrets are internal and required to Service.
     *
     * ex: MySQL database, root password, username
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getInternal(Entity\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findInternal($service));
    }

    /**
     * Owned, not internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getNotInternal(Entity\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findNotInternal($service));
    }

    /**
     * Granted, not owned
     *
     * @param Entity\Service $service
     * @return Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getGranted(Entity\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findGranted($service));
    }

    /**
     * Not granted
     *
     * @param Entity\Service $service
     * @return Entity\Secret[] Keyed by Entity\Secret.name
     */
    public function getNotGranted(Entity\Service $service) : array
    {
        $project = $service->getProject();

        return $this->sortProjectSecrets($this->repo->findNotGranted($project, $service));
    }

    /**
     * Creates Secrets owned by Service
     * Name, file, target values come from "name"
     *
     * @param Entity\Service $service
     * @param array          $toCreate [ProjectSecret name => ProjectSecret contents]
     * @param bool           $internal Mark ServiceSecrets as internal
     */
    public function createOwnedSecrets(
        Entity\Service $service,
        array $toCreate,
        bool $internal = false
    ) {
        $project = $service->getProject();

        foreach ($toCreate as $name => $contents) {
            $projectSecret = new Entity\Secret();
            $projectSecret->setName($name)
                ->setFile("./secrets/{$name}")
                ->setData($contents)
                ->setProject($service->getProject())
                ->setOwner($service);

            $serviceSecret = new Entity\ServiceSecret();
            $serviceSecret->setProjectSecret($projectSecret)
                ->setService($service)
                ->setTarget($name)
                ->setIsInternal($internal);

            $projectSecret->addServiceSecret($serviceSecret);

            $service->addSecret($serviceSecret);
            $project->addSecret($projectSecret);

            $this->repo->persist($projectSecret, $serviceSecret);
        }

        $this->repo->persist($service,  $project);
        $this->repo->flush();
    }

    /**
     * Update internal secrets. Only ProjectSecret contents is updated.
     *
     * @param Entity\Service $service
     * @param string[]       $toUpdate [name => contents]
     */
    public function updateInternal(
        Entity\Service $service,
        array $toUpdate
    ) {
        $serviceSecrets = $this->getInternal($service);

        foreach ($toUpdate as $name => $contents) {
            if (empty($serviceSecrets[$name])) {
                continue;
            }

            $serviceSecrets[$name]
                ->getProjectSecret()
                ->setData($contents);

            $this->repo->persist($serviceSecrets[$name]);
        }

        $this->repo->flush();
    }

    /**
     * Update owned, not internal secrets. ProjectSecret name, file, contents
     * and ServiceSecret target updated.
     *
     * Creates ServiceSecret if it does not previously exist
     *
     * @param Entity\Service $service
     * @param string[]       $toUpdate [Project Secret name, Project Secret contents]
     */
    public function updateOwned(
        Entity\Service $service,
        array $toUpdate
    ) {
        $serviceSecrets = $this->getNotInternal($service);

        $toCreate = [];
        foreach ($toUpdate as $row) {
            if (empty($serviceSecrets[$row['name']])) {
                $toCreate [$row['name']]= $row['contents'];

                continue;
            }

            $serviceSecret = $serviceSecrets[$row['name']];
            $projectSecret = $serviceSecret->getProjectSecret();

            unset($serviceSecrets[$row['name']]);

            $serviceSecret->setTarget($row['name']);
            $projectSecret->setName($row['name'])
                ->setFile("./secrets/{$row['name']}")
                ->setData($row['contents']);

            $this->repo->persist($serviceSecret, $projectSecret);
        }

        // Delete secrets not included in $toUpdate
        foreach ($serviceSecrets as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            foreach ($projectSecret->getServiceSecrets() as $child) {
                $child->setProjectSecret(null);
                $projectSecret->removeServiceSecret($child);

                $this->repo->remove($child);
            }

            $projectSecret->setOwner(null)
                ->removeServiceSecret($serviceSecret);
            $serviceSecret->setProjectSecret(null);
            $service->removeSecret($serviceSecret);

            $this->repo->remove($projectSecret, $serviceSecret);
        }

        $this->repo->flush();

        if (!empty($toCreate)) {
            $this->createOwnedSecrets($service, $toCreate);
        }
    }

    /**
     * Grants non-owned Secrets to Service
     *
     * @param Entity\Service $service
     * @param array          $toGrant [Project Secret id, Service Secret target]
     */
    public function grantSecrets(
        Entity\Service $service,
        array $toGrant
    ) {
        $project = $service->getProject();

        // Clear existing granted Secrets from Service
        $this->repo->deleteGrantedNotOwned($service);

        if (empty($toGrant)) {
            return;
        }

        $projectSecrets = [];
        foreach ($this->repo->findAllByProject($project) as $projectSecret) {
            $id = $projectSecret->getId() ?? $projectSecret->getSlug();

            $projectSecrets [$id]= $projectSecret;
        }

        foreach ($toGrant as $row) {
            if (empty($row['id'])) {
                continue;
            }

            if (empty($projectSecrets[$row['id']])) {
                continue;
            }

            $projectSecret = $projectSecrets[$row['id']];

            $serviceSecret = new Entity\ServiceSecret();
            $serviceSecret->setProjectSecret($projectSecret)
                ->setService($service)
                ->setTarget($row['target']);

            $projectSecret->addServiceSecret($serviceSecret);

            $service->addSecret($serviceSecret);

            $this->repo->persist($projectSecret, $serviceSecret);
        }

        $this->repo->persist($service);
        $this->repo->flush();
    }

    /**
     * Sorts Project Secrets by owner Service name then Secret name
     *
     * @param Entity\Secret[] $projectSecrets
     * @return Entity\Secret[]
     */
    private function sortProjectSecrets(array $projectSecrets) : array
    {
        $arr = [];

        foreach ($projectSecrets as $projectSecret) {
            $owner = $projectSecret->getOwner();

            $arr [$owner->getSlug()][$projectSecret->getName()]= $projectSecret;
        }

        ksort($arr);

        $sorted = [];
        foreach ($arr as $key => $secrets) {
            ksort($arr[$key]);

            $sorted = array_merge($sorted, $arr[$key]);
        }

        return $sorted;
    }

    /**
     * Sorts Service Secrets by owner Service name then Secret name
     *
     * @param Entity\ServiceSecret[] $serviceSecrets
     * @return Entity\ServiceSecret[]
     */
    private function sortServiceSecrets(array $serviceSecrets) : array
    {
        $arr = [];

        foreach ($serviceSecrets as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();
            $owner         = $projectSecret->getOwner();

            $arr [$owner->getSlug()][$projectSecret->getName()]= $serviceSecret;
        }

        ksort($arr);

        $sorted = [];
        foreach ($arr as $key => $secrets) {
            ksort($arr[$key]);

            $sorted = array_merge($sorted, $arr[$key]);
        }

        return $sorted;
    }
}
