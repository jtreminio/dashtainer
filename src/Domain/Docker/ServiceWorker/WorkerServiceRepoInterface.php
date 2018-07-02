<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Repository\Docker as Repository;

interface WorkerServiceRepoInterface
{
    public function setRepo(Repository\Service $repo);
}
