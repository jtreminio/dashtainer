<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class DockerService
{
    /** @var Repository\DockerServiceRepository */
    protected $repo;

    public function __construct(Repository\DockerServiceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createServiceFromForm(
        Form\DockerServiceCreateForm $form
    ) : Entity\DockerService {
        $service = new Entity\DockerService();
        $service->fromArray($form->toArray());

        $this->repo->save($service);

        return $service;
    }

    public function generateServiceName(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType,
        string $version = null
    ) : string {
        $services = $this->repo->findBy([
            'project'      => $project,
            'service_type' => $serviceType,
        ]);

        $version = $version ? "-{$version}" : '';

        if (empty($services)) {
            return "{$serviceType->getSlug()}{$version}";
        }

        $usedNames = [];
        foreach ($services as $service) {
            $usedNames []= $service->getName();
        }

        for ($i = 1; $i <= count($usedNames); $i++) {
            $name = "{$serviceType->getSlug()}{$version}-{$i}";

            if (!in_array($name, $usedNames)) {
                return $name;
            }
        }

        return "{$serviceType->getSlug()}{$version}-" . uniqid();
    }
}
