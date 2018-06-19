<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker as Domain;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Repository\Docker as Repository;

use Doctrine\Common\Collections;

abstract class WorkerAbstract implements WorkerInterface
{
    /** @var Domain\Network */
    protected $networkDomain;

    /** @var Domain\Secret */
    protected $secretDomain;

    /** @var Domain\Service */
    protected $serviceDomain;

    /** @var Entity\ServiceType */
    protected $serviceType;

    /** @var Repository\Service */
    protected $serviceRepo;

    /** @var Domain\Volume */
    protected $volumeDomain;

    /** @var string */
    protected $version;

    /** @var Domain\WorkerBag */
    protected $workerBag;

    public function __construct(
        Repository\Service $serviceRepo,
        Domain\Network $networkDomain,
        Domain\Secret $secretDomain,
        Domain\Service $serviceDomain,
        Domain\Volume $volumeDomain
    ) {
        $this->serviceRepo     = $serviceRepo;

        $this->networkDomain = $networkDomain;
        $this->secretDomain  = $secretDomain;
        $this->serviceDomain = $serviceDomain;
        $this->volumeDomain  = $volumeDomain;
    }

    public function setVersion(string $version = null)
    {
        $this->version = $version;
    }

    public function setServiceType(Entity\ServiceType $serviceType)
    {
        $this->serviceType = $serviceType;
    }

    public function getServiceType() : Entity\ServiceType
    {
        return $this->serviceType;
    }

    public function setWorkerBag(Domain\WorkerBag $workerBag)
    {
        $this->workerBag = $workerBag;
    }

    public function getCreateParams(Entity\Project $project): array
    {
        $serviceName = $this->serviceDomain->generateName(
            $project,
            $this->serviceType,
            $this->version
        );

        return [
            'serviceName' => $serviceName,
            'version'     => $this->version,
            'networks'    => $this->getCreateNetworks($project),
            'ports'       => $this->getCreatePorts(),
            'secrets'     => $this->getCreateSecrets($project),
            'volumes'     => $this->getCreateVolumes($project),
        ];
    }

    public function getViewParams(Entity\Service $service) : array
    {
        return [
            'networks' => $this->getViewNetworks($service),
            'ports'    => $this->getViewPorts($service),
            'secrets'  => $this->getViewSecrets($service),
            'volumes'  => $this->getViewVolumes($service),
        ];
    }

    public function delete(Entity\Service $service)
    {
        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);

            $this->serviceRepo->remove($meta);
        }

        $this->secretDomain->deleteAllForService($service);
        $this->volumeDomain->deleteAllForService($service);

        if ($parent = $service->getParent()) {
            $parent->removeChild($service);

            $this->serviceRepo->persist($parent);
        }

        foreach ($service->getChildren() as $child) {
            $service->removeChild($child);

            $this->serviceRepo->remove($child);
        }

        $this->serviceRepo->remove($service);
        $this->serviceRepo->flush();
    }

    protected function getCreateNetworks(Entity\Project $project) : array
    {
        return $this->networkDomain->getForNewService($project, $this->internalNetworksArray());
    }

    protected function getViewNetworks(Entity\Service $service) : array
    {
        return $this->networkDomain->getForExistingService($service, $this->internalNetworksArray());
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function createNetworks(Entity\Service $service, $form)
    {
        $this->networkDomain->save($service, $form->networks);
        $this->networkDomain->deleteEmptyNetworks($service->getProject());
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function updateNetworks(Entity\Service $service, $form)
    {
        $this->createNetworks($service, $form);
    }

    protected function getCreatePorts() : Collections\ArrayCollection
    {
        $ports = new Collections\ArrayCollection();
        foreach ($this->internalPortsArray() as $data) {
            $port = new Entity\ServicePort();
            $port->fromArray(['id' => uniqid()]);
            $port->setPublished($data[0])
                ->setTarget($data[1])
                ->setProtocol($data[2]);

            $ports->set($port->getId(), $port);
        }

        return $ports;
    }

    protected function getViewPorts(Entity\Service $service) : iterable
    {
        return $service->getPorts();
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function createPorts(
        Entity\Service $service,
        $form
    ) {
        foreach ($form->ports as $data) {
            $port = new Entity\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($service);

            $this->serviceRepo->persist($port);
        }
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function updatePorts(
        Entity\Service $service,
        $form
    ) {
        $delete = [];

        foreach ($service->getPorts() as $port) {
            $service->removePort($port);

            $this->serviceRepo->remove($port);
        }

        foreach ($form->ports as $data) {
            $port = new Entity\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($service);

            $this->serviceRepo->persist($port);
        }

        $this->serviceRepo->persist($service);
        $this->serviceRepo->remove($delete);
        $this->serviceRepo->flush();
    }

    protected function getCreateSecrets(Entity\Project $project) : array
    {
        return $this->secretDomain->getForNewService(
            $project,
            $this->serviceType,
            $this->internalSecretsArray()
        );
    }

    protected function getViewSecrets(Entity\Service $service) : array
    {
        return $this->secretDomain->getForExistingService(
            $service,
            $this->serviceType,
            $this->internalSecretsArray()
        );
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function createSecrets(
        Entity\Service $service,
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
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function updateSecrets(
        Entity\Service $service,
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
     * @param Entity\Project $project
     * @return Collections\ArrayCollection[]
     */
    protected function getCreateVolumes(Entity\Project $project) : array
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
     * @param Entity\Service $service
     * @return Collections\ArrayCollection[]
     */
    protected function getViewVolumes(Entity\Service $service) : array
    {
        return $this->volumeDomain->getForExistingService(
            $service,
            $this->serviceType,
            $this->internalVolumesArray()
        );
    }

    /**
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function createVolumes(
        Entity\Service $service,
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
     * @param Entity\Service              $service
     * @param Form\Service\CreateAbstract $form
     */
    protected function updateVolumes(
        Entity\Service $service,
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
