<?php

namespace Dashtainer\Repository\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

class Network implements Repository\ObjectPersistInterface
{
    protected const ENTITY_CLASS = Entity\Docker\Network::class;

    /** @var ORM\EntityManagerInterface */
    protected $em;

    /** @var Persistence\ObjectRepository */
    protected $repo;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository(self::ENTITY_CLASS);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Network|null
     */
    public function find($id) : ?Entity\Docker\Network
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Network[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Network[]
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) : array {
        return $this->repo->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     * @return Entity\Docker\Network|null
     */
    public function findOneBy(array $criteria) : ?Entity\Docker\Network
    {
        return $this->repo->findOneBy($criteria);
    }

    public function save(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->persist($ent);
        }

        $this->em->flush();
    }

    public function delete(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->remove($ent);
        }

        $this->em->flush();
    }

    public function getClassName() : string
    {
        return self::ENTITY_CLASS;
    }

    /**
     * @param Entity\Docker\Project $project
     * @return Entity\Docker\Network[]
     */
    public function findAllByProject(Entity\Docker\Project $project) : array
    {
        return $this->repo->findBy(['project' => $project]);
    }

    public function findByProject(
        Entity\Docker\Project $project,
        string $id
    ) : ?Entity\Docker\Network {
        return $this->findOneBy([
            'id'      => $id,
            'project' => $project,
        ]);
    }

    /**
     * @param Entity\Docker\Service $service
     * @param bool                  $public
     * @return Entity\Docker\Network[]
     */
    public function findByService(Entity\Docker\Service $service, bool $public = false) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from('Dashtainer:Docker\Network', 'n')
            ->where(':service MEMBER OF n.services');

        if (!$public) {
            $qb->andWhere('n.is_primary_public = 0');
        }

        $qb->setParameters(['service' => $service]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Entity\Docker\Service $service
     * @param bool                  $public
     * @return Entity\Docker\Network[]
     */
    public function findByNotService(Entity\Docker\Service $service, bool $public = false) : array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from('Dashtainer:Docker\Network', 'n')
            ->where(':service NOT MEMBER OF n.services');

        if (!$public) {
            $qb->andWhere('n.is_primary_public = 0');
        }

        $qb->setParameters(['service' => $service]);

        return $qb->getQuery()->getResult();
    }

    public function getPrimaryPublicNetwork(
        Entity\Docker\Project $project
    ) : ?Entity\Docker\Network {
        return $this->findOneBy([
            'project'           => $project,
            'is_primary_public' => true,
        ]);
    }

    public function getPrimaryPrivateNetwork(
        Entity\Docker\Project $project
    ) : ?Entity\Docker\Network {
        return $this->findOneBy([
            'project'            => $project,
            'is_primary_private' => true,
        ]);
    }

    public function getPrivateNetworks(
        Entity\Docker\Project $project
    ) : array {
        $publicNetwork = $this->getPrimaryPublicNetwork($project);

        $allNetworks = $this->findAllByProject($project);

        foreach ($allNetworks as $key => $network) {
            if ($network->getId() === $publicNetwork->getId()) {
                unset($allNetworks[$key]);

                break;
            }
        }

        return $allNetworks;
    }
}
