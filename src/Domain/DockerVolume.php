<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerVolume
{
    /** @var Repository\DockerVolumeRepository */
    protected $repo;

    public function __construct(Repository\DockerVolumeRepository $repo)
    {
        $this->repo = $repo;
    }
}
