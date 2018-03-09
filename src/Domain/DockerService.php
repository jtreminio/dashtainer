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

    /** @var Handler\CrudInterface[] */
    protected $handler = [];

    public function __construct(
        Repository\DockerServiceRepository $repo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->repo        = $repo;
        $this->networkRepo = $networkRepo;
    }

    /**
     * @inheritdoc
     * @required
     */
    public function setServiceHandlers(
        Handler\Apache $apache,
        Handler\PhpFpm $phpfpm
    ) {
        $this->handler []= $apache;
        $this->handler []= $phpfpm;
    }

    public function createService(
        Form\DockerServiceCreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->getHandlerFromForm($form);

        return $handler->create($form);
    }

    public function deleteService(Entity\DockerService $service)
    {
        $handler = $this->getHandlerFromType($service->getType());

        $handler->delete($service);
    }

    public function updateService(
        Entity\DockerService $service,
        Form\DockerServiceCreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->getHandlerFromForm($form);

        return $handler->update($service, $form);
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType
    ) : Form\DockerServiceCreateAbstract {
        $handler = $this->getHandlerFromType($serviceType);

        return $handler->getCreateForm($serviceType);
    }

    public function getCreateParams(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType
    ) : array {
        $handler = $this->getHandlerFromType($serviceType);

        return $handler->getCreateParams($project);
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        $handler = $this->getHandlerFromType($service->getType());

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

    protected function getHandlerFromForm(
        Form\DockerServiceCreateAbstract $form
    ) : Handler\CrudInterface {
        foreach ($this->handler as $handler) {
            if (is_a($form, get_class($handler->getCreateForm()))) {
                return $handler;
            }
        }

        return null;
    }

    protected function getHandlerFromType(
        Entity\DockerServiceType $type
    ) : Handler\CrudInterface {
        foreach ($this->handler as $handler) {
            if ($type->getSlug() == $handler->getServiceTypeSlug()) {
                return $handler;
            }
        }

        return null;
    }
}
