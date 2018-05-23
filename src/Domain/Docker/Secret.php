<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

class Secret
{
    /** @var Repository\Docker\Secret */
    protected $repo;

    public function __construct(Repository\Docker\Secret $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Returns array of IDs that do not belong to Project
     *
     * @param Entity\Docker\Project $project
     * @param int[]                 $ids
     * @return int[]
     */
    public function idsNotBelongToProject(
        Entity\Docker\Project $project,
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
     * @param Entity\Docker\Service $service
     * @param int[]                 $ids
     * @return int[]
     */
    public function idsNotBelongToService(
        Entity\Docker\Service $service,
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
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Secret[] Keyed by Entity\Docker\Secret.name
     */
    public function getAll(Entity\Docker\Project $project) : array
    {
        return $this->sortProjectSecrets($this->repo->findAllByProject($project));
    }

    /**
     * All internal and not-internal ServiceSecrets owned by Service
     *So
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[] Keyed by Entity\Docker\Secret.name
     */
    public function getOwned(Entity\Docker\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findOwned($service));
    }

    /**
     * Internal Secrets are internal and required to Service.
     *
     * ex: MySQL database, root password, username
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[] Keyed by Entity\Docker\Secret.name
     */
    public function getInternal(Entity\Docker\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findInternal($service));
    }

    /**
     * Owned, not internal
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[] Keyed by Entity\Docker\Secret.name
     */
    public function getNotInternal(Entity\Docker\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findNotInternal($service));
    }

    /**
     * Granted, not owned
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[] Keyed by Entity\Docker\Secret.name
     */
    public function getGranted(Entity\Docker\Service $service) : array
    {
        return $this->sortServiceSecrets($this->repo->findGranted($service));
    }

    /**
     * Not granted
     *
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\Secret[] Keyed by Entity\Docker\Secret.name
     */
    public function getNotGranted(Entity\Docker\Service $service) : array
    {
        $project = $service->getProject();

        return $this->sortProjectSecrets($this->repo->findNotGranted($project, $service));
    }

    /**
     * Creates Secrets owned by Service
     * Name, file, target values come from "name"
     *
     * @param Entity\Docker\Service $service
     * @param array                 $toCreate [ProjectSecret name => ProjectSecret contents]
     * @param bool                  $internal Mark ServiceSecrets as internal
     */
    public function createOwnedSecrets(
        Entity\Docker\Service $service,
        array $toCreate,
        bool $internal = false
    ) {
        $project = $service->getProject();

        $saved = [];
        foreach ($toCreate as $name => $contents) {
            $projectSecret = new Entity\Docker\Secret();
            $projectSecret->setName($name)
                ->setFile("./secrets/{$name}")
                ->setContents($contents)
                ->setProject($service->getProject())
                ->setOwner($service);

            $serviceSecret = new Entity\Docker\ServiceSecret();
            $serviceSecret->setProjectSecret($projectSecret)
                ->setService($service)
                ->setTarget($name)
                ->setIsInternal($internal);

            $projectSecret->addServiceSecret($serviceSecret);

            $service->addSecret($serviceSecret);
            $project->addSecret($projectSecret);

            $saved []= $projectSecret;
            $saved []= $serviceSecret;
        }

        $this->repo->save(
            $service,
            $project,
            ...$saved
        );
    }

    /**
     * Update internal secrets. Only ProjectSecret contents is updated.
     *
     * @param Entity\Docker\Service $service
     * @param string[]              $toUpdate [name => contents]
     */
    public function updateInternal(
        Entity\Docker\Service $service,
        array $toUpdate
    ) {
        $serviceSecrets = $this->getInternal($service);

        $saved = [];
        foreach ($toUpdate as $name => $contents) {
            if (empty($serviceSecrets[$name])) {
                continue;
            }

            $serviceSecrets[$name]
                ->getProjectSecret()
                ->setContents($contents);

            $saved []= $serviceSecrets[$name];
        }

        $this->repo->save(...$saved);
    }

    /**
     * Update owned, not internal secrets. ProjectSecret name, file, contents
     * and ServiceSecret target updated.
     *
     * Creates ServiceSecret if it does not previously exist
     *
     * @param Entity\Docker\Service $service
     * @param string[]              $toUpdate [Project Secret name, Project Secret contents]
     */
    public function updateOwned(
        Entity\Docker\Service $service,
        array $toUpdate
    ) {
        $serviceSecrets = $this->getNotInternal($service);

        $saved    = [];
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
                ->setContents($row['contents']);

            $saved []= $serviceSecret;
            $saved []= $projectSecret;
        }

        // Delete secrets not included in $toUpdate
        $toDelete = [];
        foreach ($serviceSecrets as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            foreach ($projectSecret->getServiceSecrets() as $child) {
                $child->setProjectSecret(null);
                $projectSecret->removeServiceSecret($child);

                $toDelete []= $child;
            }

            $projectSecret->setOwner(null)
                ->removeServiceSecret($serviceSecret);
            $serviceSecret->setProjectSecret(null);
            $service->removeSecret($serviceSecret);

            $toDelete []= $projectSecret;
            $toDelete []= $serviceSecret;
        }

        $this->repo->save(...$saved);
        $this->repo->delete(...$toDelete);

        if (!empty($toCreate)) {
            $this->createOwnedSecrets($service, $toCreate);
        }
    }

    /**
     * Grants non-owned Secrets to Service
     *
     * @param Entity\Docker\Service $service
     * @param array                 $toGrant [Project Secret id, Service Secret target]
     */
    public function grantSecrets(
        Entity\Docker\Service $service,
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

        $saved = [];
        foreach ($toGrant as $row) {
            if (empty($row['id'])) {
                continue;
            }

            if (empty($projectSecrets[$row['id']])) {
                continue;
            }

            $projectSecret = $projectSecrets[$row['id']];

            $serviceSecret = new Entity\Docker\ServiceSecret();
            $serviceSecret->setProjectSecret($projectSecret)
                ->setService($service)
                ->setTarget($row['target']);

            $projectSecret->addServiceSecret($serviceSecret);

            $service->addSecret($serviceSecret);

            $saved []= $projectSecret;
            $saved []= $serviceSecret;
        }

        $this->repo->save(
            $service,
            ...$saved
        );
    }

    /**
     * Sorts Project Secrets by owner Service name then Secret name
     *
     * @param Entity\Docker\Secret[] $projectSecrets
     * @return Entity\Docker\Secret[]
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
     * @param Entity\Docker\ServiceSecret[] $serviceSecrets
     * @return Entity\Docker\ServiceSecret[]
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
