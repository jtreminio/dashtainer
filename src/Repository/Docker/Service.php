<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository;
use function PHPSTORM_META\type;

class Service extends Repository\ObjectPersistAbstract
{
    protected const ENTITY_CLASS = Entity\Service::class;

    public function findByProjectAndId(Entity\Project $project, string $id) : ?Entity\Service
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->andWhere('s.project = :project')
            ->andWhere('s.id = :id')
            ->setParameters([
                'project' => $project,
                'id'      => $id,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Service[]
     */
    public function findAllByProject(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->andWhere('s.project = :project')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @return Entity\Service[]
     */
    public function findAllPublicByProject(Entity\Project $project) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('s.type', 'st')
            ->andWhere('st.is_public <> 0')
            ->andWhere('s.project = :project')
            ->setParameters([
                'project' => $project,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @param string         $name
     * @return Entity\Service
     */
    public function findByProjectAndName(Entity\Project $project, string $name) : ?Entity\Service
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->andWhere('s.project = :project')
            ->andWhere('s.name = :name')
            ->setParameters([
                'project' => $project,
                'name'    => $name,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Entity\Project     $project
     * @param Entity\ServiceType $type
     * @return Entity\Service[]
     */
    public function findByProjectAndType(Entity\Project $project, Entity\ServiceType $type) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('s.type', 'st')
            ->andWhere('s.project = :project')
            ->andWhere('s.type = :type')
            ->setParameters([
                'project' => $project,
                'type'    => $type,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @param string         $typeName
     * @return Entity\Service[]
     */
    public function findByProjectAndTypeName(Entity\Project $project, string $typeName) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('s.type', 'st')
            ->andWhere('s.project = :project')
            ->andWhere('st.name = :typeName')
            ->setParameters([
                'project'  => $project,
                'typeName' => $typeName,
            ]);

        return $qb->getQuery()->getResult();
    }

    public function findChildByType(
        Entity\Service $parent,
        Entity\ServiceType $childType
    ) : ?Entity\Service {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('s.type', 'st')
            ->andWhere('s.parent = :parent')
            ->andWhere('s.type = :type')
            ->setParameters([
                'parent' => $parent,
                'type'   => $childType,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findChildByTypeName(
        Entity\Service $parent,
        string $typeName
    ) : ?Entity\Service {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->join('s.type', 'st')
            ->andWhere('s.parent = :parent')
            ->andWhere('st.slug = :typeName')
            ->setParameters([
                'parent'   => $parent,
                'typeName' => $typeName,
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Entity\Network $network
     * @return Entity\Service[]
     */
    public function findByNotNetwork(Entity\Network $network) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from('Dashtainer:Docker\Service', 's')
            ->andWhere(':network NOT MEMBER OF s.networks')
            ->setParameters([
                'network' => $network,
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Project $project
     * @param Entity\Service $excludeService
     * @return Entity\ServicePort[]
     */
    public function getProjectPorts(
        Entity\Project $project,
        Entity\Service $excludeService = null
    ) : array {
        $qb = $this->em->createQueryBuilder()
            ->select('sp')
            ->from('Dashtainer:Docker\ServicePort', 'sp')
            ->join('sp.service', 's')
            ->join('s.project', 'p')
            ->andWhere('s.project = :project');

        $params = [
            'project' => $project,
        ];

        if ($excludeService) {
            $qb->andWhere('sp.service <> :service');

            $params = [
                'project' => $project,
                'service' => $excludeService,
            ];
        }

        $qb->setParameters($params);

        return $qb->getQuery()->getResult();
    }
}
