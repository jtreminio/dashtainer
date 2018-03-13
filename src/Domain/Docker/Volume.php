<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Repository;
use Dashtainer\Util;

class Volume
{
    /** @var Repository\Docker\Volume */
    protected $repo;

    public function __construct(Repository\Docker\Volume $repo)
    {
        $this->repo = $repo;
    }
}
