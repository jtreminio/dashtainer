<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker\Volume;

use Doctrine\Common\Collections;

class RepoDockerVolume extends Volume
{
    public function findAllByProject(Entity\Project $project) : array
    {
        return $project->getVolumes()->toArray();
    }

    public function findByIds(Entity\Project $project, array $ids) : array
    {
        $volumes = [];

        foreach ($project->getVolumes() as $volume) {
            // ->andWhere('v.id IN (:ids)')
            if (!in_array($volume->getId(), $ids)) {
                continue;
            }

            $volumes []= $volume;
        }

        return $volumes;
    }

    public function findOwnedProjectVolumes(Entity\Service $service) : array
    {
        $volumes = [];

        foreach ($service->getVolumes() as $serviceVolume) {
            // ->join('v.service_volumes', 'sv')
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            // ->andWhere('v.owner = :service')
            if ($projectVolume->getOwner() !== $service) {
                continue;
            }

            $volumes []= $projectVolume;
        }

        return $volumes;
    }

    public function findOwnedServiceVolumes(Entity\Service $service) : array
    {
        $volumes = [];

        foreach ($service->getVolumes() as $serviceVolume) {
            // ->join('sv.project_volume', 'v')
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            // ->andWhere('v.owner = :service')
            if ($projectVolume->getOwner() !== $service) {
                continue;
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }

    public function findInternal(Entity\Service $service) : array
    {
        $volumes = [];

        // ->andWhere('sv.service = :service')
        foreach ($service->getVolumes() as $serviceVolume) {
            // ->join('sv.project_volume', 'v')
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            // ->andWhere('v.owner = :service')
            if ($projectVolume->getOwner() !== $service) {
                continue;
            }

            // ->andWhere('sv.is_internal <> 0')
            if (!$serviceVolume->getIsInternal()) {
                continue;
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }

    public function findNotInternal(Entity\Service $service) : array
    {
        $volumes = [];

        // ->andWhere('sv.service = :service')
        foreach ($service->getVolumes() as $serviceVolume) {
            // ->join('sv.project_volume', 'v')
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            // ->andWhere('v.owner = :service')
            if ($projectVolume->getOwner() !== $service) {
                continue;
            }

            // ->andWhere('sv.is_internal = 0')
            if ($serviceVolume->getIsInternal()) {
                continue;
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }

    public function findGranted(Entity\Service $service) : array
    {
        $volumes = [];

        // ->andWhere('sv.service = :service')
        foreach ($service->getVolumes() as $serviceVolume) {
            // ->join('sv.project_volume', 'v')
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            // ->andWhere('v.owner <> :service')
            if ($projectVolume->getOwner() === $service) {
                continue;
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }

    public function findNotGranted(
        Entity\Project $project,
        Entity\Service $service
    ) : array {
        $granted = new Collections\ArrayCollection();

        foreach ($service->getVolumes() as $serviceVolume) {
            if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                continue;
            }

            if ($projectVolume->getOwner() === $service) {
                continue;
            }

            $granted->add($projectVolume);
        }

        $volumes = [];

        foreach ($project->getVolumes() as $volume) {
            // $qb->andWhere('v.id NOT IN (:granted)');
            if ($granted->contains($volume)) {
                continue;
            }

            $volumes []= $volume;
        }

        return $volumes;
    }

    public function findByName(
        Entity\Service $service,
        array $names
    ) : array {
        $volumes = [];

        foreach ($service->getVolumes() as $serviceVolume) {
            // ->andWhere('sv.name IN (:names)')
            if (!in_array($serviceVolume->getName(), $names)) {
                continue;
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }

    public function findByFileType(
        Entity\Service $service,
        string $fileType,
        bool $internal
    ) : array {
        $volumes = [];

        foreach ($service->getVolumes() as $serviceVolume) {
            // ->andWhere('sv.filetype = :filetype')
            if ($serviceVolume->getFiletype() !== $fileType) {
                continue;
            }

            // ->andWhere('sv.is_internal = :internal')
            if ($serviceVolume->getIsInternal() !== $internal) {
                continue;
            }

            // ->andWhere('v.owner = :service OR v.owner IS NULL')
            if ($projectVolume = $serviceVolume->getProjectVolume()) {
                if ($projectVolume->getOwner() !== $service) {
                    continue;
                }
            }

            $volumes []= $serviceVolume;
        }

        return $volumes;
    }
}
