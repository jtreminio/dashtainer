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
            'networkName' => $this->networkDomain->generateName($project),
            'networks'    => $this->getCreateNetworks($project),
            'secrets'     => $this->getCreateSecrets($project),
            'volumes'     => $this->getCreateVolumes($project),
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return [
            'networks' => $this->getViewNetworks($service),
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

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function addToPrivateNetworks(Entity\Docker\Service $service, $form)
    {
        $this->networkDomain->joinNetworks($service, $form->networks_join);
        $this->networkDomain->createNetworksForService($service, $form->networks_create);
        $this->networkDomain->deleteEmptyNetworks($service->getProject());
    }

    protected function getCreateNetworks(Entity\Docker\Project $project) : array
    {
        return [
            'joined'   => [],
            'unjoined' => $this->networkDomain->getPrivateNetworks($project),
        ];
    }

    protected function getViewNetworks(Entity\Docker\Service $service) : array
    {
        return [
            'joined'   => $this->networkDomain->findByService($service),
            'unjoined' => $this->networkDomain->findByNotService($service),
        ];
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

    abstract protected function internalVolumesArray() : array;

    abstract protected function internalSecretsArray() : array;
}
