<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;
use Dashtainer\Util;

use Doctrine\Common\Collections\ArrayCollection;

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
        $project = $service->getProject();

        foreach ($this->repo->findOwnedProjectSecrets($service) as $projectSecret) {
            foreach ($projectSecret->getServiceSecrets() as $serviceSecret) {
                $subService = $serviceSecret->getService();

                $projectSecret->removeServiceSecret($serviceSecret);
                $projectSecret->setOwner(null);
                $subService->removeSecret($serviceSecret);
                $this->repo->remove($serviceSecret);
            }

            $project->removeSecret($projectSecret);
            $this->repo->remove($projectSecret);
        }

        foreach ($this->repo->findOwnedServiceSecrets($service) as $serviceSecret) {
            $service->removeSecret($serviceSecret);
            $serviceSecret->setProjectSecret(null);
            $this->repo->remove($serviceSecret);
        }

        foreach ($this->repo->findGranted($service) as $serviceSecret) {
            if ($projectSecret = $serviceSecret->getProjectSecret()) {
                $projectSecret->removeServiceSecret($serviceSecret);
            }

            $service->removeSecret($serviceSecret);
            $this->repo->remove($serviceSecret);
        }
    }

    /**
     * Returns Secrets required for new Service
     *
     * @param Entity\Project     $project
     * @param Entity\ServiceType $serviceType
     * @param array              $internalSecretsArray
     * @return ArrayCollection[]
     */
    public function getForNewService(
        Entity\Project $project,
        Entity\ServiceType $serviceType,
        array $internalSecretsArray
    ) : array {
        $internal = new ArrayCollection();

        foreach ($internalSecretsArray as $metaName) {
            $data = $serviceType->getMeta($metaName)->getData();

            $projectSecret = new Entity\Secret();
            $projectSecret->fromArray(['id' => $data['name']]);
            $projectSecret->setName($data['name'])
                ->setData($data['data']);

            $serviceSecret = new Entity\ServiceSecret();
            $serviceSecret->fromArray(['id' => $data['name']]);
            $serviceSecret->setName($data['name'])
                ->setTarget($data['name'])
                ->setIsInternal(true)
                ->setProjectSecret($projectSecret);

            $internal->set($data['name'], $serviceSecret);
        }

        return [
            'owned'     => $internal,
            'granted'   => new ArrayCollection(),
            'grantable' => $this->getAllServiceSecrets($project),
        ];
    }

    /**
     * Returns Secrets required for existing Service
     *
     * @param Entity\Service     $service
     * @param Entity\ServiceType $serviceType
     * @param array              $internalSecretsArray
     * @return ArrayCollection[]
     */
    public function getForExistingService(
        Entity\Service $service,
        Entity\ServiceType $serviceType,
        array $internalSecretsArray
    ) : array {
        $owned = new ArrayCollection();

        $internalMetaSecrets = [];
        foreach ($internalSecretsArray as $metaName) {
            if (!$meta = $serviceType->getMeta($metaName)) {
                continue;
            }

            $data = $meta->getData();
            $internalMetaSecrets [$data['name']]= $data;
        }

        $existingInternal = $this->getInternalFromNames($service, array_keys($internalMetaSecrets));
        foreach ($existingInternal as $name => $secret) {
            if (empty($secret)) {
                $data = $internalMetaSecrets[$name];

                $projectSecret = new Entity\Secret();
                $projectSecret->fromArray(['id' => $data['name']]);
                $projectSecret->setName($data['name'])
                    ->setData($data['data']);

                $secret = new Entity\ServiceSecret();
                $secret->fromArray(['id' => $data['name']]);
                $secret->setName($data['name'])
                    ->setTarget($data['name'])
                    ->setIsInternal(true)
                    ->setProjectSecret($projectSecret);
            }

            $owned->set($name, $secret);
        }

        foreach ($this->getNotInternal($service) as $name => $secret) {
            $owned->set($name, $secret);
        }

        return [
            'owned'     => $owned,
            'granted'   => $this->getGranted($service),
            'grantable' => $this->getNotGranted($service),
        ];
    }

    /**
     * All Service Secrets belonging to Project
     *
     * @param Entity\Project $project
     * @return ArrayCollection|Entity\ServiceSecret[] Keyed by Entity\ServiceSecret.name
     */
    public function getAllServiceSecrets(Entity\Project $project) : ArrayCollection
    {
        return $this->sortServiceSecrets($this->repo->findAllServiceSecretsByProject($project));
    }

    /**
     * Internal Secrets are internal and required to Service.
     *
     * ex: MySQL database, root password, username
     *
     * @param Entity\Service $service
     * @return ArrayCollection|Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getInternal(Entity\Service $service) : ArrayCollection
    {
        return $this->sortServiceSecrets($this->repo->findInternal($service));
    }

    /**
     * Owned, not internal
     *
     * @param Entity\Service $service
     * @return ArrayCollection|Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getNotInternal(Entity\Service $service) : ArrayCollection
    {
        return $this->sortServiceSecrets($this->repo->findNotInternal($service));
    }

    /**
     * Granted, not owned
     *
     * @param Entity\Service $service
     * @return ArrayCollection|Entity\ServiceSecret[] Keyed by Entity\Secret.name
     */
    public function getGranted(Entity\Service $service) : ArrayCollection
    {
        return $this->sortServiceSecrets($this->repo->findGranted($service));
    }

    /**
     * Not granted
     *
     * @param Entity\Service $service
     * @return ArrayCollection|Entity\ServiceSecret[] Keyed by Entity\ServiceSecret.name
     */
    public function getNotGranted(Entity\Service $service) : ArrayCollection
    {
        $project = $service->getProject();

        return $this->sortServiceSecrets($this->repo->findNotGranted($project, $service));
    }

    /**
     * Creates and updates internal and not-internal Secrets
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceSecret[] $internalMetaSecrets Hydrated from ServiceTypeMeta data
     * @param array                  $configs             User-provided data from form
     */
    public function save(
        Entity\Service $service,
        array $internalMetaSecrets,
        array $configs
    ) {
        $configs = $this->saveInternal($service, $internalMetaSecrets, $configs);
        $this->saveNotInternal($service, $configs);
    }

    /**
     * Creates and updates internal Secrets
     *
     * All Services will have 0 or more internal Secrets with default data.
     * If no user data is passed, default data is used
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceSecret[] $serviceSecrets Hydrated from ServiceTypeMeta data
     * @param array                  $configs        User-provided data from form
     * @return array Array without internal Secrets, containing only non-internal User Secrets
     */
    protected function saveInternal(
        Entity\Service $service,
        array $serviceSecrets,
        array $configs
    ) : array {
        $project = $service->getProject();

        foreach ($serviceSecrets as $serviceSecret) {
            if (!$serviceSecret->getIsInternal()) {
                continue;
            }

            $id   = $serviceSecret->getId();
            $data = $configs[$id] ?? [];

            $projectSecret = $serviceSecret->getProjectSecret();

            $name = $projectSecret->getName();
            $file = $projectSecret->getFile();

            // If new Secret, id and name will be identical
            if ($projectSecret->getId() === $projectSecret->getName()) {
                $name = Util\Strings::filename("{$service->getSlug()}-{$data['name']}");
                $file = "./secrets/{$name}";
            }

            $projectSecret->setName($name)
                ->setFile($file)
                ->setData($data['data'] ?? '')
                ->setProject($project)
                ->setOwner($service);

            $serviceSecret->setName($data['name'] ?? $serviceSecret->getName())
                ->setTarget($data['name'] ?? $serviceSecret->getTarget())
                ->setService($service);

            $this->repo->persist($projectSecret, $serviceSecret);

            unset($configs[$id]);
        }

        $this->repo->persist($project, $service);

        return $configs;
    }

    /**
     * Creates and updates not-internal Secrets
     *
     * @param Entity\Service $service
     * @param array          $configs User-provided data from form
     */
    protected function saveNotInternal(
        Entity\Service $service,
        array $configs
    ) {
        $project = $service->getProject();

        $serviceSecrets = [];
        foreach ($this->getNotInternal($service) as $serviceSecret) {
            $serviceSecrets [$serviceSecret->getId()] = $serviceSecret;
        }

        foreach ($configs as $id => $data) {
            if (!array_key_exists($id, $serviceSecrets)) {
                $serviceSecret = new Entity\ServiceSecret();
                $serviceSecret->setService($service);

                $projectSecret = new Entity\Secret();
                $projectSecret->addServiceSecret($serviceSecret)
                    ->setProject($project)
                    ->setOwner($service);

                $serviceSecrets [$id]= $serviceSecret;
            }

            $projectSecretName = Util\Strings::filename("{$service->getSlug()}-{$data['name']}");
            $serviceSecretName = Util\Strings::filename($data['name']);

            /** @var Entity\ServiceSecret $serviceSecret */
            $serviceSecret = $serviceSecrets[$id];
            $serviceSecret->setName($serviceSecretName)
                ->setTarget($serviceSecretName);

            $projectSecret = $serviceSecret->getProjectSecret();
            $projectSecret->setName($projectSecretName)
                ->setFile("./secrets/{$projectSecretName}")
                ->setData($data['data'] ?? '');

            $this->repo->persist($projectSecret, $serviceSecret);
            unset($serviceSecrets[$id]);
        }

        // No longer wanted by user
        foreach ($serviceSecrets as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            $service->removeSecret($serviceSecret);
            $projectSecret->removeServiceSecret($serviceSecret);

            foreach ($projectSecret->getServiceSecrets() as $schild) {
                $projectSecret->removeServiceSecret($schild);

                $this->repo->remove($schild);
            }

            $this->repo->remove($projectSecret, $serviceSecret);
        }

        $this->repo->persist($service);
    }

    /**
     * Grants non-owned Secrets to Service
     *
     * @param Entity\Service $service
     * @param array          $toGrant [Project Secret id, Owner Service Secret name, Service Secret target]
     */
    public function grant(
        Entity\Service $service,
        array $toGrant
    ) {
        // Clear existing granted Secrets from Service
        foreach ($this->repo->findGranted($service) as $serviceSecret) {
            if ($projectSecret = $serviceSecret->getProjectSecret()) {
                $projectSecret->removeServiceSecret($serviceSecret);
            }

            $service->removeSecret($serviceSecret);
            $this->repo->remove($serviceSecret);
        }

        if (empty($toGrant)) {
            return;
        }

        $project = $service->getProject();

        $projectSecrets = [];
        foreach ($this->repo->findByIds($project, array_column($toGrant, 'id')) as $projectSecret) {
            $id = $projectSecret->getId() ?? $projectSecret->getSlug();

            $projectSecrets [$id]= $projectSecret;
        }

        foreach ($toGrant as $data) {
            if (empty($data['id'])) {
                continue;
            }

            if (empty($projectSecrets[$data['id']])) {
                continue;
            }

            /** @var Entity\Secret $projectSecret */
            $projectSecret = $projectSecrets[$data['id']];

            $serviceSecret = new Entity\ServiceSecret();
            $serviceSecret->setName(Util\Strings::filename($data['name'])) // should be from owner
                ->setTarget(Util\Strings::filename($data['target']))
                ->setProjectSecret($projectSecret)
                ->setService($service);

            $this->repo->persist($projectSecret, $serviceSecret);
        }

        $this->repo->persist($service);
    }

    /**
     * @param Entity\Service $service
     * @param array          $names
     * @return Entity\ServiceSecret[]
     */
    protected function getInternalFromNames(
        Entity\Service $service,
        array $names
    ) : array {
        $secrets = $this->repo->findByName(
            $service,
            $names
        );

        $sorted = array_fill_keys($names, null);

        foreach ($secrets as $secret) {
            $sorted [$secret->getName()]= $secret;
        }

        return $sorted;
    }

    /**
     * Sorts Service Secrets by owner Service name then Secret name
     *
     * @param Entity\ServiceSecret[] $serviceSecrets
     * @return ArrayCollection|Entity\ServiceSecret[]
     */
    private function sortServiceSecrets(array $serviceSecrets) : ArrayCollection
    {
        $serviceArr = [];

        foreach ($serviceSecrets as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();
            $owner         = $projectSecret->getOwner();

            $serviceArr [$owner->getSlug()][$projectSecret->getName()]= $serviceSecret;
        }

        ksort($serviceArr);

        $sorted = new ArrayCollection();
        foreach ($serviceArr as $secrets) {
            ksort($secrets);

            foreach ($secrets as $projectSecretName => $serviceSecret) {
                $sorted->set($projectSecretName, $serviceSecret);
            }
        }

        return $sorted;
    }
}
