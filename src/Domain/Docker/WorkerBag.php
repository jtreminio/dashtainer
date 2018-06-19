<?php

namespace Dashtainer\Domain\Docker;

class WorkerBag
{
    /** @var ServiceWorker\WorkerInterface[] */
    protected $workers = [];

    /**
     * @param ServiceWorker\WorkerInterface[]|iterable $workers
     * @param ServiceType                              $serviceType
     */
    public function __construct(iterable $workers, ServiceType $serviceType)
    {
        foreach ($workers as $worker) {
            $this->workers [$worker::SERVICE_TYPE_SLUG]= $worker;
        }

        foreach ($serviceType->getAllBySlugs(array_keys($this->workers)) as $type) {
            $this->workers[$type->getSlug()]->setServiceType($type);
            $this->workers[$type->getSlug()]->setWorkerBag($this);
        }
    }

    public function getWorkerFromType(string $typeSlug) : ?ServiceWorker\WorkerInterface
    {
        return $this->workers[$typeSlug] ?? null;
    }
}
