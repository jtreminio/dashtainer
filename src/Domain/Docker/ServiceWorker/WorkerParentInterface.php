<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

interface WorkerParentInterface
{
    public function manageChildren() : array;
}
