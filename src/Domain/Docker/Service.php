<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class Service
{
    /** @var Repository\Docker\Service */
    protected $repo;

    /** @var HandlerStore */
    protected $serviceHandler;

    public function __construct(
        Repository\Docker\Service $repo,
        HandlerStore $serviceHandler
    ) {
        $this->repo           = $repo;
        $this->serviceHandler = $serviceHandler;
    }

    public function createService(
        Form\Docker\Service\CreateAbstract $form
    ) : Entity\Docker\Service {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->create($form);
    }

    public function deleteService(Entity\Docker\Service $service)
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        $handler->delete($service);
    }

    public function updateService(
        Entity\Docker\Service $service,
        Form\Docker\Service\CreateAbstract $form
    ) : Entity\Docker\Service {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->update($service, $form);
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType
    ) : Form\Docker\Service\CreateAbstract {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateForm($serviceType);
    }

    public function getCreateParams(
        Entity\Docker\Project $project,
        Entity\Docker\ServiceType $serviceType
    ) : array {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateParams($project);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        return $handler->getViewParams($service);
    }

    public function generateServiceName(
        Entity\Docker\Project $project,
        Entity\Docker\ServiceType $serviceType,
        string $version = null
    ) : string {
        $services = $this->repo->findBy([
            'project' => $project,
            'type'    => $serviceType,
        ]);

        $version  = $version ? "-{$version}" : '';
        $hostname = Util\Strings::hostname("{$serviceType->getSlug()}{$version}");

        if (empty($services)) {
            return $hostname;
        }

        $usedNames = [];
        foreach ($services as $service) {
            $usedNames []= $service->getName();
        }

        for ($i = 1; $i <= count($usedNames); $i++) {
            $name = "{$hostname}-{$i}";

            if (!in_array($name, $usedNames)) {
                return $name;
            }
        }

        return "{$hostname}-" . uniqid();
    }
}
