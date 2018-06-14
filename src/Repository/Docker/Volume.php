<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;

use Doctrine\ORM;

class Volume extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\Volume::class;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository(self::ENTITY_CLASS);
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Volume[]
     */
    public function findAllByProject(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->addSelect('sv')
            ->from('Dashtainer:Docker\Volume', 'v')
            ->join('v.service_volumes', 'sv')
            ->andWhere('v.project = :project')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @param array          $ids
     * @return Entity\Volume[]
     */
    public function findByIds(Entity\Project $project, array $ids) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from('Dashtainer:Docker\Volume', 'v')
            ->andWhere('v.id IN (:ids)')
            ->andWhere('v.project = :project')
            ->setParameters([
                'project' => $project,
                'ids'     => $ids,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Volumes owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    public function findOwned(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->join('sv.project_volume', 'v')
            ->andWhere('v.owner = :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Volumes owned by Service and marked as internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    public function findInternal(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->join('sv.project_volume', 'v')
            ->andWhere('v.owner = :service')
            ->andWhere('sv.service = :service')
            ->andWhere('sv.is_internal = 1')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Volumes owned by Service and marked as not internal
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    public function findNotInternal(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->join('sv.project_volume', 'v')
            ->andWhere('v.owner = :service')
            ->andWhere('sv.service = :service')
            ->andWhere('sv.is_internal = 1')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Volumes granted to but not owned by Service
     *
     * @param Entity\Service $service
     * @return Entity\ServiceVolume[]
     */
    public function findGranted(Entity\Service $service) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->join('sv.project_volume', 'v')
            ->andWhere('sv.service = :service')
            ->andWhere('v.owner != :service')
            ->setParameters([
                'service' => $service,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Volumes not granted to Service
     *
     * @param Entity\Project $project
     * @param Entity\Service $service
     * @return Entity\Volume[]
     */
    public function findNotGranted(
        Entity\Project $project,
        Entity\Service $service
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('v.id')
            ->from('Dashtainer:Docker\Volume', 'v')
            ->join('v.service_volumes', 'sv')
            ->andWhere('sv.service = :service')
            ->andWhere('v.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        $granted = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $granted []= $item['id'];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from('Dashtainer:Docker\Volume', 'v')
            ->andWhere('v.project = :project')
            ->andWhere('v.owner <> :service');

        $parameters = [
            'project' => $project,
            'service' => $service,
        ];

        if ($granted) {
            $qb->andWhere('v.id NOT IN (:granted)');

            $parameters = [
                'project' => $project,
                'service' => $service,
                'granted' => $granted,
            ];
        }

        $qb->setParameters($parameters);

        $notGranted = $qb->getQuery()->getResult();

        return $notGranted;
    }

    public function deleteVolumes(Entity\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->addSelect('sv')
            ->from('Dashtainer:Docker\Volume', 'v')
            ->join('v.service_volumes', 'sv')
            ->andWhere('v.owner = :service')
            ->setParameters([
                'service' => $service,
            ]);

        /** @var Entity\Volume $projectVolume */
        foreach ($qb->getQuery()->getResult() as $projectVolume) {
            foreach ($projectVolume->getServiceVolumes() as $serviceVolume) {
                $projectVolume->removeServiceVolume($serviceVolume);

                $this->em->remove($serviceVolume);
            }

            $this->em->remove($projectVolume);
        }

        $this->em->flush();
    }

    public function deleteServiceVolumes(Entity\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->andWhere('sv.service = :service')
            ->setParameters([
                'service' => $service,
            ]);

        /** @var Entity\ServiceVolume $serviceVolume */
        foreach ($qb->getQuery()->getResult() as $serviceVolume) {
            $this->em->remove($serviceVolume);
        }

        $this->em->flush();
    }

    public function deleteGrantedNotOwned(Entity\Service $service)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->join('sv.project_volume', 'v')
            ->andWhere('sv.service = :service')
            ->andWhere('v.owner <> :service')
            ->setParameters([
                'service' => $service,
            ]);

        foreach ($qb->getQuery()->getResult() as $item) {
            $this->em->remove($item);
        }

        $this->em->flush();
    }

    /**
     * @param Entity\Service $service
     * @param array          $names
     * @return Entity\ServiceVolume[]
     */
    public function findByName(
        Entity\Service $service,
        array $names
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->leftJoin('sv.project_volume', 'v')
            ->andWhere('sv.service = :service')
            ->andWhere('sv.name IN (:names)')
            ->setParameters([
                'service' => $service,
                'names'   => $names,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Service $service
     * @param string         $fileType
     * @param bool           $internal
     * @return Entity\ServiceVolume[]
     */
    public function findByFileType(
        Entity\Service $service,
        string $fileType,
        bool $internal
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('sv')
            ->addSelect('v')
            ->from('Dashtainer:Docker\ServiceVolume', 'sv')
            ->leftJoin('sv.project_volume', 'v')
            ->andWhere('sv.service = :service')
            ->andWhere('sv.filetype = :filetype')
            ->andWhere('sv.is_internal = :internal')
            ->andWhere('v.owner = :service OR v.owner IS NULL')
            ->setParameters([
                'service'  => $service,
                'filetype' => $fileType,
                'internal' => $internal,
            ]);

        return $qb->getQuery()->getResult();
    }
}
