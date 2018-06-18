<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\Common\Collections;

abstract class WorkerAbstract implements WorkerInterface
{
    /** @var Domain\Docker\Network */
    protected $networkDomain;

    /** @var Domain\Docker\Secret */
    protected $secretDomain;

    /** @var Entity\Docker\ServiceType */
    protected $serviceType;

    /** @var Repository\Docker\Service */
    protected $serviceRepo;

    /** @var Repository\Docker\ServiceType */
    protected $serviceTypeRepo;

    /** @var Domain\Docker\Volume */
    protected $volumeDomain;

    protected $version;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\ServiceType $serviceTypeRepo,
        Domain\Docker\Network $networkDomain,
        Domain\Docker\Secret $secretDomain,
        Domain\Docker\Volume $volumeDomain
    ) {
        $this->serviceRepo     = $serviceRepo;
        $this->serviceTypeRepo = $serviceTypeRepo;

        $this->networkDomain = $networkDomain;
        $this->secretDomain  = $secretDomain;
        $this->volumeDomain  = $volumeDomain;
    }

    public function setVersion(string $version = null)
    {
        $this->version = $version;
    }

    public function getCreateParams(Entity\Docker\Project $project): array
    {
        return [
            'networks' => $this->getCreateNetworks($project),
            'ports'    => $this->getCreatePorts(),
            'secrets'  => $this->getCreateSecrets($project),
            'volumes'  => $this->getCreateVolumes($project),
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return [
            'networks' => $this->getViewNetworks($service),
            'ports'    => $this->getViewPorts($service),
            'secrets'  => $this->getViewSecrets($service),
            'volumes'  => $this->getViewVolumes($service),
        ];
    }

    public function delete(Entity\Docker\Service $service)
    {
        $metas = [];
        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);

            $metas []= $meta;
        }

        $this->secretDomain->deleteAllForService($service);
        $this->volumeDomain->deleteAllForService($service);

        if ($parent = $service->getParent()) {
            $service->setParent(null);
            $parent->removeChild($service);

            $this->serviceRepo->save($parent);
        }

        $children = [];
        foreach ($service->getChildren() as $child) {
            $child->setParent(null);
            $service->removeChild($child);

            $children []= $child;
        }

        $this->serviceRepo->delete(...$metas, ...$children);
        $this->serviceRepo->delete($service);
    }

    protected function getCreateNetworks(Entity\Docker\Project $project) : array
    {
        return $this->networkDomain->getForNewService($project, $this->internalNetworksArray());
    }

    protected function getViewNetworks(Entity\Docker\Service $service) : array
    {
        return $this->networkDomain->getForExistingService($service, $this->internalNetworksArray());
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function createNetworks(Entity\Docker\Service $service, $form)
    {
        $this->networkDomain->save($service, $form->networks);
        $this->networkDomain->deleteEmptyNetworks($service->getProject());
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function updateNetworks(Entity\Docker\Service $service, $form)
    {
        $this->createNetworks($service, $form);
    }

    protected function getCreatePorts() : Collections\ArrayCollection
    {
        $ports = new Collections\ArrayCollection();
        foreach ($this->internalPortsArray() as $data) {
            $port = new Entity\Docker\ServicePort();
            $port->fromArray(['id' => uniqid()]);
            $port->setPublished($data[0])
                ->setTarget($data[1])
                ->setProtocol($data[2]);

            $ports->set($port->getId(), $port);
        }

        return $ports;
    }

    protected function getViewPorts(Entity\Docker\Service $service) : iterable
    {
        return $service->getPorts();
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function createPorts(
        Entity\Docker\Service $service,
        $form
    ) {
        $saved = [];
        foreach ($form->ports as $data) {
            $port = new Entity\Docker\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($service);

            $saved []= $port;
        }

        $this->serviceRepo->save($service, ...$saved);
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function updatePorts(
        Entity\Docker\Service $service,
        $form
    ) {
        $saved  = [];
        $delete = [];

        foreach ($service->getPorts() as $port) {
            $service->removePort($port);

            $delete []= $port;
        }

        foreach ($form->ports as $data) {
            $port = new Entity\Docker\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($service);

            $saved []= $port;
        }

        $this->serviceRepo->save($service, ...$saved);
        $this->serviceRepo->delete(...$delete);
    }

    protected function getCreateSecrets(Entity\Docker\Project $project) : array
    {
        return $this->secretDomain->getForNewService(
            $project,
            $this->serviceType,
            $this->internalSecretsArray()
        );
    }

    protected function getViewSecrets(Entity\Docker\Service $service) : array
    {
        return $this->secretDomain->getForExistingService(
            $service,
            $this->serviceType,
            $this->internalSecretsArray()
        );
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function createSecrets(
        Entity\Docker\Service $service,
        $form
    ) {
        $secrets = $this->getCreateSecrets($service->getProject());

        $this->secretDomain->save(
            $service,
            $secrets['owned']->toArray(),
            $form->secrets
        );

        $this->secretDomain->grant($service, $form->secrets_granted);
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function updateSecrets(
        Entity\Docker\Service $service,
        $form
    ) {
        $secrets = $this->getViewSecrets($service);

        $this->secretDomain->save(
            $service,
            $secrets['owned']->toArray(),
            $form->secrets
        );

        $this->secretDomain->grant($service, $form->secrets_granted);
    }

    /**
     * Returns non-persisted ServiceVolumes [name => metaName] hydrated
     * from ServiceTypeMeta data
     *
     * @param Entity\Docker\Project $project
     * @return Collections\ArrayCollection[]
     */
    protected function getCreateVolumes(Entity\Docker\Project $project) : array
    {
        return $this->volumeDomain->getForNewService(
            $project,
            $this->serviceType,
            $this->internalVolumesArray()
        );
    }

    /**
     * Returns persisted ServiceVolumes [name => metaName] hydrated
     * from ServiceTypeMeta data
     *
     * @param Entity\Docker\Service $service
     * @return Collections\ArrayCollection[]
     */
    protected function getViewVolumes(Entity\Docker\Service $service) : array
    {
        return $this->volumeDomain->getForExistingService(
            $service,
            $this->serviceType,
            $this->internalVolumesArray()
        );
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function createVolumes(
        Entity\Docker\Service $service,
        $form
    ) {
        $volumes = $this->getCreateVolumes($service->getProject());

        $this->volumeDomain->saveFile(
            $service,
            $volumes['files']->toArray(),
            $form->volumes_file
        );

        $this->volumeDomain->saveOther(
            $service,
            $volumes['other']->toArray(),
            $form->volumes_other
        );

        $this->volumeDomain->grant($service, $form->volumes_granted);
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function updateVolumes(
        Entity\Docker\Service $service,
        $form
    ) {
        $volumes = $this->getViewVolumes($service);

        $this->volumeDomain->saveFile(
            $service,
            $volumes['files']->toArray(),
            $form->volumes_file
        );

        $this->volumeDomain->saveOther(
            $service,
            $volumes['other']->toArray(),
            $form->volumes_other
        );

        $this->volumeDomain->grant($service, $form->volumes_granted);
    }

    abstract protected function internalNetworksArray() : array;

    abstract protected function internalPortsArray() : array;

    abstract protected function internalSecretsArray() : array;

    abstract protected function internalVolumesArray() : array;
}
