<?php

namespace Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

abstract class ObjectPersistAbstract
{
    /** @var ORM\EntityManagerInterface */
    protected $em;

    /** @var Persistence\ObjectRepository */
    protected $repo;

    public function remove(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->remove($ent);
        }
    }

    public function flush()
    {
        $this->em->flush();
    }

    public function persist(object ...$entity)
    {
        foreach ($entity as $ent) {
            $this->em->persist($ent);
        }
    }
}
