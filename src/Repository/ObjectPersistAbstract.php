<?php

namespace Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

abstract class ObjectPersistAbstract
{
    protected const ENTITY_CLASS = null;

    /** @var ORM\EntityManagerInterface */
    protected $em;

    /** @var Persistence\ObjectRepository */
    protected $repo;

    public function __construct(ORM\EntityManagerInterface $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository(static::ENTITY_CLASS);
    }

    public function flush()
    {
        $this->em->flush();
    }

    public function remove(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->remove($ent);
        }
    }

    public function persist(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->persist($ent);
        }
    }
}
