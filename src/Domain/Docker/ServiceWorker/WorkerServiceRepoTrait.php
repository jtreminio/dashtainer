<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Repository\Docker as Repository;

trait WorkerServiceRepoTrait
{
    /** @var Repository\Service */
    protected $repo;

    public function setRepo(Repository\Service $repo)
    {
        $this->repo = $repo;
    }
}
