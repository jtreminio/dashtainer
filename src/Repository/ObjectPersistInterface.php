<?php

namespace Dashtainer\Repository;

use Doctrine\Common\Persistence;

interface ObjectPersistInterface extends Persistence\ObjectRepository
{
    public function save(object ...$entity);

    public function delete(object ...$entity);
}
