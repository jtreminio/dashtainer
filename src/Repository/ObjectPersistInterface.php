<?php

namespace Dashtainer\Repository;

use Doctrine\ORM;
use Doctrine\Common\Persistence;

interface ObjectPersistInterface extends Persistence\ObjectRepository
{
    public function save(object ...$entity);
}
