<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerService
{
    /** @var Repository\DockerServiceRepository */
    protected $repo;

    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var ServiceHandlerStore */
    protected $serviceHandler;

    public function __construct(
        Repository\DockerServiceRepository $repo,
        Repository\DockerNetworkRepository $networkRepo,
        ServiceHandlerStore $serviceHandler
    ) {
        $this->repo           = $repo;
        $this->networkRepo    = $networkRepo;
        $this->serviceHandler = $serviceHandler;
    }

    public function createService(
        Form\Service\CreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->create($form);
    }

    public function deleteService(Entity\DockerService $service)
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        $handler->delete($service);
    }

    public function updateService(
        Entity\DockerService $service,
        Form\Service\CreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->update($service, $form);
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType
    ) : Form\Service\CreateAbstract {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateForm($serviceType);
    }

    public function getCreateParams(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType
    ) : array {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateParams($project);
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        return $handler->getViewParams($service);
    }

    public function generateServiceName(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType,
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
