<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;
use Dashtainer\Util;

use Doctrine\Common\Collections;

class Volume
{
    /** @var Repository\Volume */
    protected $repo;

    public function __construct(Repository\Volume $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Deletes all Volumes owned or assigned to Service
     *
     * @param Entity\Service $service
     */
    public function deleteAllForService(Entity\Service $service)
    {
        $this->repo->deleteVolumes($service);
        $this->repo->deleteServiceVolumes($service);
        $this->repo->deleteGrantedNotOwned($service);
    }

    /**
     * Returns Volumes required for new Service
     *
     * @param Entity\Project     $project
     * @param Entity\ServiceType $serviceType
     * @param array              $internalVolumesArray
     * @return array
     */
    public function getForNewService(
        Entity\Project $project,
        Entity\ServiceType $serviceType,
        array $internalVolumesArray
    ) {
        $files = new Collections\ArrayCollection();
        $other = new Collections\ArrayCollection();

        foreach ($internalVolumesArray['files'] as $metaName) {
            $data = $serviceType->getMeta($metaName)->getData();

            $volume = new Entity\ServiceVolume();
            $volume->fromArray(['id' => $data['name']]);
            $volume->setName($data['name'])
                ->setSource($data['source'])
                ->setTarget($data['target'])
                ->setHighlight($data['highlight'])
                ->setData($data['data'])
                ->setIsInternal(true)
                ->setFiletype($data['filetype'])
                ->setType($data['type']);

            $files->set($data['name'], $volume);
        }

        foreach ($internalVolumesArray['other'] as $metaName) {
            $data = $serviceType->getMeta($metaName)->getData();

            $volume = new Entity\ServiceVolume();
            $volume->fromArray(['id' => $data['name']]);
            $volume->setName($data['name'])
                ->setSource($data['source'])
                ->setTarget($data['target'])
                ->setIsInternal(true)
                ->setFiletype($data['filetype'])
                ->setType($data['type']);

            $other->set($data['name'], $volume);
        }

        return [
            'files'     => $files,
            'other'     => $other,
            'granted'   => [],
            'grantable' => $this->getAll($project),
        ];
    }

    /**
     * Returns Volumes required for existing Service
     *
     * @param Entity\Service     $service
     * @param Entity\ServiceType $serviceType
     * @param array              $internalVolumesArray
     * @return array
     */
    public function getForExistingService(
        Entity\Service $service,
        Entity\ServiceType $serviceType,
        array $internalVolumesArray
    ) {
        $files = new Collections\ArrayCollection();
        $other = new Collections\ArrayCollection();

        $internalFileNames = [];
        foreach ($internalVolumesArray['files'] as $metaName) {
            if (!$meta = $serviceType->getMeta($metaName)) {
                continue;
            }

            $data = $meta->getData();
            $internalFileNames []= $data['name'];
        }

        $internalFiles = $this->getInternalFromNames(
            $service,
            $internalFileNames
        );
        foreach ($internalFiles as $name => $volume) {
            $files->set($name, $volume);
        }

        foreach ($this->getNotInternalFile($service) as $name => $volume) {
            $files->set($name, $volume);
        }

        $internalOtherNames = [];
        foreach ($internalVolumesArray['other'] as $metaName) {
            if (!$meta = $serviceType->getMeta($metaName)) {
                continue;
            }

            $data = $meta->getData();
            $internalOtherNames []= $data['name'];
        }

        $internalOther = $this->getInternalFromNames(
            $service,
            $internalOtherNames
        );
        foreach ($internalOther as $name => $volume) {
            $other->set($name, $volume);
        }

        foreach ($this->getNotInternalOther($service) as $name => $volume) {
            $other->set($name, $volume);
        }

        $granted   = $this->getGranted($service);
        $grantable = $this->getNotGranted($service);

        return [
            'files'     => $files,
            'other'     => $other,
            'granted'   => $granted,
            'grantable' => $grantable,
        ];
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Volume[]
     */
    public function getAll(Entity\Project $project) : array
    {
        return $this->sortProjectVolumes($this->repo->findAllByProject($project));
    }

    /**
     * All internal and not-internal Volumes owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    public function getOwned(Entity\Service $service) : array
    {
        return $this->sortServiceVolumes($this->repo->findOwned($service));
    }

    /**
     * Internal Volume are internal and required to Service.
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[] Keyed by Entity\Volume.name
     */
    public function getInternal(Entity\Service $service) : array
    {
        return $this->sortServiceVolumes($this->repo->findInternal($service));
    }

    /**
     * Owned, not internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[] Keyed by Entity\Volume.name
     */
    public function getNotInternal(Entity\Service $service) : array
    {
        return $this->sortServiceVolumes($this->repo->findNotInternal($service));
    }

    /**
     * Granted, not owned
     *
     * @param Entity\Service $service
     * @return Entity\Volume[]
     */
    public function getGranted(Entity\Service $service) : array
    {
        return $this->sortServiceVolumes($this->repo->findGranted($service));
    }

    /**
     * Not granted
     *
     * @param Entity\Service $service
     * @return Entity\Volume[]
     */
    public function getNotGranted(Entity\Service $service) : array
    {
        $project = $service->getProject();

        return $this->sortProjectVolumes($this->repo->findNotGranted($project, $service));
    }

    /**
     * Grants non-owned Volumes to Service
     *
     * @param Entity\Service $service
     * @param array          $toGrant [Project Volume id, Service Volume target]
     */
    public function grant(
        Entity\Service $service,
        array $toGrant
    ) {
        $project = $service->getProject();

        // Clear existing granted Volumes from Service
        $this->repo->deleteGrantedNotOwned($service);

        if (empty($toGrant)) {
            return;
        }

        $projectVolumes = [];
        foreach ($this->repo->findByIds($project, array_column($toGrant, 'id')) as $projectVolume) {
            $id = $projectVolume->getId() ?? $projectVolume->getSlug();

            $projectVolumes [$id]= $projectVolume;
        }

        foreach ($toGrant as $row) {
            if (empty($row['id'])) {
                continue;
            }

            if (empty($projectVolumes[$row['id']])) {
                continue;
            }

            /** @var Entity\Volume $projectVolume */
            $projectVolume = $projectVolumes[$row['id']];

            $serviceVolume = new Entity\ServiceVolume();
            $serviceVolume->setName("{$projectVolume->getId()}-granted")
                ->setSource('volume')
                ->setTarget($row['target'])
                ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
                ->setType(Entity\ServiceVolume::TYPE_VOLUME)
                ->setProjectVolume($projectVolume)
                ->setService($service);

            $this->repo->persist($projectVolume, $serviceVolume);
        }
    }

    /**
     * Creates and updates internal and not-internal File-based Volumes
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceVolume[] $internalMetaVolumes Hydrated from ServiceTypeMeta data
     * @param array                  $configs             User-provided data from form
     */
    public function saveFile(
        Entity\Service $service,
        array $internalMetaVolumes,
        array $configs
    ) {
        $configs = $this->saveInternalFile($service, $internalMetaVolumes, $configs);
        $this->saveNotInternalFile($service, $configs);
    }

    /**
     * Creates and updates internal File-based Volumes
     *
     * All Services will have 0 or more internal Volumes with default data.
     * If no user data is passed, default data is used
     *
     * Internal name, source and target values should never change
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceVolume[] $serviceVolumes Hydrated from ServiceTypeMeta data
     * @param array                  $configs        User-provided data from form
     * @return array Array without internal Volumes, containing only non-internal User Volumes
     */
    protected function saveInternalFile(
        Entity\Service $service,
        array $serviceVolumes,
        array $configs
    ) : array {
        foreach ($serviceVolumes as $serviceVolume) {
            if (!$serviceVolume->getIsInternal()) {
                continue;
            }

            $id   = $serviceVolume->getId();
            $data = $configs[$id] ?? [];

            // "source" and "target" are not user-definable for internal Volumes
            $serviceVolume->setData($data['data'] ?? '')
                ->setConsistency(Entity\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setService($service);

            $this->repo->persist($serviceVolume);

            unset($configs[$id]);
        }

        return $configs;
    }

    /**
     * Creates and updates not-internal File-based Volumes
     *
     * @param Entity\Service $service
     * @param array          $configs User-provided data from form
     */
    protected function saveNotInternalFile(
        Entity\Service $service,
        array $configs
    ) {
        $serviceVolumes = [];
        foreach ($this->getNotInternalFile($service) as $serviceVolume) {
            $serviceVolumes [$serviceVolume->getId()] = $serviceVolume;
        }

        foreach ($configs as $id => $data) {
            $name = Util\Strings::filename($data['source']);

            if (!array_key_exists($id, $serviceVolumes)) {
                $serviceVolume = new Entity\ServiceVolume();
                $serviceVolume->setName(uniqid("{$name}-"))
                    ->setType(Entity\ServiceVolume::TYPE_BIND)
                    ->setFiletype(Entity\ServiceVolume::FILETYPE_FILE)
                    ->setConsistency(Entity\ServiceVolume::CONSISTENCY_DELEGATED)
                    ->setService($service);

                $serviceVolumes [$id]= $serviceVolume;
            }

            /** @var Entity\ServiceVolume $serviceVolume */
            $serviceVolume = $serviceVolumes[$id];
            $serviceVolume->setSource($name)
                ->setTarget($data['target'])
                ->setData($data['data']);

            $this->repo->persist($serviceVolume);
            unset($serviceVolumes[$id]);
        }

        // No longer wanted by user
        foreach ($serviceVolumes as $serviceVolume) {
            $service->removeVolume($serviceVolume);
        }

        $this->repo->remove(...array_values($serviceVolumes));
    }

    /**
     * Creates and updates internal and not-internal non-File-based Volumes (no data stored)
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceVolume[] $internalMetaVolumes Hydrated from ServiceTypeMeta data
     * @param array                  $configs             User-provided data from form
     */
    public function saveOther(
        Entity\Service $service,
        array $internalMetaVolumes,
        array $configs
    ) {
        $configs = $this->saveInternalOther($service, $internalMetaVolumes, $configs);
        $this->saveNotInternalOther($service, $configs);
    }

    /**
     * Creates and updates internal non-File-based Volumes
     *
     * All Services will have 0 or more internal Volumes with default data.
     * If no user data is passed, default data is used
     *
     * Internal name, source and target values should never change
     *
     * @param Entity\Service         $service
     * @param Entity\ServiceVolume[] $serviceVolumes Hydrated from ServiceTypeMeta data
     * @param array                  $configs        User-provided data from form
     * @return array Array without internal Volumes, containing only non-internal User Volumes
     */
    protected function saveInternalOther(
        Entity\Service $service,
        array $serviceVolumes,
        array $configs
    ) {
        $project = $service->getProject();

        foreach ($serviceVolumes as $serviceVolume) {
            if (!$serviceVolume->getIsInternal()) {
                continue;
            }

            $id   = $serviceVolume->getId();
            $data = $configs[$id] ?? [];

            $serviceVolume->setType($data['type'] ?? $serviceVolume->getType())
                ->setSource($data['source'] ?? $serviceVolume->getSource())
                ->setConsistency(Entity\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setService($service);

            if ($serviceVolume->getType() === Entity\ServiceVolume::TYPE_VOLUME) {
                // create Project Volume if not exist
                if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                    $projectVolume = new Entity\Volume();
                    $projectVolume->setName("{$service->getSlug()}-{$serviceVolume->getSlug()}")
                        ->setProject($project)
                        ->addServiceVolume($serviceVolume)
                        ->setOwner($service);

                    $this->repo->persist($projectVolume);
                }
            }

            if ($serviceVolume->getType() === Entity\ServiceVolume::TYPE_BIND) {
                // remove project volume if exist
                if ($projectVolume = $serviceVolume->getProjectVolume()) {
                    $projectVolume->removeServiceVolume($serviceVolume);

                    // Remove other Service's grants to this Project Volume
                    foreach ($projectVolume->getServiceVolumes() as $granted) {
                        $projectVolume->removeServiceVolume($granted);

                        $this->repo->remove($granted);
                    }

                    $project->removeVolume($projectVolume);
                }
            }

            $this->repo->persist($serviceVolume);
            unset($configs[$id]);
        }

        $this->repo->persist($project, $service);

        return $configs;
    }

    /**
     * Creates and updates not-internal non-File-based Volumes
     *
     * @param Entity\Service $service
     * @param array          $configs User-provided data from form
     */
    protected function saveNotInternalOther(
        Entity\Service $service,
        array $configs
    ) {
        $project = $service->getProject();

        $serviceVolumes = [];
        foreach ($this->getNotInternalOther($service) as $serviceVolume) {
            $serviceVolumes [$serviceVolume->getId()] = $serviceVolume;
        }

        foreach ($configs as $id => $data) {
            $name = $data['name'] ?? Util\Strings::filename($data['source']);

            if (!array_key_exists($id, $serviceVolumes)) {
                $serviceVolume = new Entity\ServiceVolume();
                $serviceVolume->setName($name)
                    ->setFiletype(Entity\ServiceVolume::FILETYPE_OTHER)
                    ->setConsistency(Entity\ServiceVolume::CONSISTENCY_DELEGATED)
                    ->setService($service);

                $serviceVolumes [$id]= $serviceVolume;
            }

            /** @var Entity\ServiceVolume $serviceVolume */
            $serviceVolume = $serviceVolumes[$id];
            $serviceVolume->setSource($data['source'])
                ->setTarget($data['target'])
                ->setType($data['type']);

            if ($serviceVolume->getType() === Entity\ServiceVolume::TYPE_VOLUME) {
                // create Project Volume if not exist
                if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                    $projectVolume = new Entity\Volume();
                    $projectVolume->setName("{$service->getSlug()}-{$serviceVolume->getSlug()}")
                        ->setProject($project)
                        ->addServiceVolume($serviceVolume)
                        ->setOwner($service);
                }

                $this->repo->persist($projectVolume);
            }

            if ($serviceVolume->getType() === Entity\ServiceVolume::TYPE_BIND) {
                // remove project volume if exist
                if ($projectVolume = $serviceVolume->getProjectVolume()) {
                    $projectVolume->removeServiceVolume($serviceVolume);

                    // Remove other Service's grants to this Project Volume
                    foreach ($projectVolume->getServiceVolumes() as $granted) {
                        $projectVolume->removeServiceVolume($granted);

                        $this->repo->remove($granted);
                    }

                    $project->removeVolume($projectVolume);
                    $this->repo->remove($projectVolume);
                }
            }

            $this->repo->persist($serviceVolume);
            unset($serviceVolumes[$id]);
        }

        // No longer wanted by user
        foreach ($serviceVolumes as $serviceVolume) {
            $service->removeVolume($serviceVolume);

            if ($projectVolume = $serviceVolume->getProjectVolume()) {
                $projectVolume->removeServiceVolume($serviceVolume);

                // Remove other Service's grants to this Project Volume
                foreach ($projectVolume->getServiceVolumes() as $granted) {
                    $projectVolume->removeServiceVolume($granted);

                    $this->repo->remove($granted);
                }

                $project->removeVolume($projectVolume);
                $this->repo->remove($projectVolume);
            }

            $this->repo->remove($serviceVolume);
        }
    }

    /**
     * @param Entity\Service $service
     * @param array          $names
     * @return Entity\ServiceVolume[]
     */
    protected function getInternalFromNames(
        Entity\Service $service,
        array $names
    ) : array {
        $volumes = $this->repo->findByName(
            $service,
            $names
        );

        $sorted = array_fill_keys($names, null);

        foreach ($volumes as $volume) {
            $sorted [$volume->getName()]= $volume;
        }

        return array_filter($sorted);
    }

    /**
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    protected function getNotInternalFile(
        Entity\Service $service
    ) : array {
        return $this->repo->findByFileType(
            $service,
            Entity\ServiceVolume::FILETYPE_FILE,
            false
        );
    }

    /**
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    protected function getNotInternalOther(
        Entity\Service $service
    ) : array {
        return $this->repo->findByFileType(
            $service,
            Entity\ServiceVolume::FILETYPE_OTHER,
            false
        );
    }

    /**
     * Sorts Project Volumes by owner Service name then Volume name
     *
     * @param Entity\Volume[] $projectVolumes
     * @return Entity\Volume[]
     */
    private function sortProjectVolumes(array $projectVolumes) : array
    {
        $arr = [];

        foreach ($projectVolumes as $projectVolume) {
            $owner = $projectVolume->getOwner();

            $arr [$owner->getSlug()][$projectVolume->getName()]= $projectVolume;
        }

        ksort($arr);

        $sorted = [];
        foreach ($arr as $key => $volumes) {
            ksort($arr[$key]);

            $sorted = array_merge($sorted, $arr[$key]);
        }

        return $sorted;
    }

    /**
     * Sorts Service Volumes by owner Service name then Volume name
     *
     * @param Entity\ServiceVolume[] $serviceVolumes
     * @return Entity\ServiceVolume[]
     */
    private function sortServiceVolumes(array $serviceVolumes) : array
    {
        $arr = [];

        foreach ($serviceVolumes as $serviceVolume) {
            $projectVolume = $serviceVolume->getProjectVolume();
            $owner         = $projectVolume->getOwner();

            $arr [$owner->getSlug()][$projectVolume->getName()]= $serviceVolume;
        }

        ksort($arr);

        $sorted = [];
        foreach ($arr as $key => $volumes) {
            ksort($arr[$key]);

            $sorted = array_merge($sorted, $arr[$key]);
        }

        return $sorted;
    }
}
