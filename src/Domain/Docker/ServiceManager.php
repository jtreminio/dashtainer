<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;

class ServiceManager
{
    /** @var ServiceWorker\WorkerInterface[] */
    protected $workers = [];

    /**
     * @param ServiceWorker\WorkerInterface[]|iterable $workers
     */
    public function __construct(iterable $workers )
    {
        foreach ($workers as $worker) {
            $this->workers []= $worker;
        }
    }

    public function getWorkerFromForm(
        Form\Docker\Service\CreateAbstract $form
    ) : ServiceWorker\WorkerInterface {
        foreach ($this->workers as $worker) {
            if (is_a($form, get_class($worker->getCreateForm()))) {
                return $worker;
            }
        }

        return null;
    }

    public function getWorkerFromType(
        Entity\Docker\ServiceType $type
    ) : ServiceWorker\WorkerInterface {
        foreach ($this->workers as $worker) {
            if ($type === $worker->getServiceType()) {
                return $worker;
            }
        }

        return null;
    }
}
