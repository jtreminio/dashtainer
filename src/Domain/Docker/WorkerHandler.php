<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Domain\Docker\ServiceWorker\WorkerParentInterface;
use Dashtainer\Domain\Docker\ServiceWorker\WorkerServiceRepoInterface;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Repository\Docker as Repository;

use Doctrine\Common\Collections;

class WorkerHandler
{
    /** @var WorkerBag */
    protected $bag;

    /** @var Network */
    protected $networkDomain;

    /** @var Repository\Service */
    protected $repo;

    /** @var Secret */
    protected $secretDomain;

    /** @var Service */
    protected $serviceDomain;

    /** @var Volume */
    protected $volumeDomain;

    /** @var ServiceWorker\WorkerInterface */
    protected $worker;

    public function __construct(
        WorkerBag $bag,
        Network $networkDomain,
        Repository\Service $repo,
        Secret $secretDomain,
        Service $serviceDomain,
        Volume $volumeDomain
    ) {
        $this->bag = $bag;

        $this->repo = $repo;

        $this->networkDomain = $networkDomain;
        $this->secretDomain  = $secretDomain;
        $this->serviceDomain = $serviceDomain;
        $this->volumeDomain  = $volumeDomain;
    }

    public function setWorkerFromServiceType(string $serviceTypeSlug) : bool
    {
        if (!$worker = $this->bag->getWorkerFromType($serviceTypeSlug)) {
            return false;
        }

        $worker->setForm($worker::getFormInstance());

        if (is_a($worker, WorkerServiceRepoInterface::class)) {
            /** @var WorkerServiceRepoInterface $worker */
            $worker->setRepo($this->repo);
        }

        $this->worker = $worker;

        return true;
    }

    /**
     * @param Form\Service\CreateAbstract $form
     * @return $this
     */
    public function setForm(Form\Service\CreateAbstract $form)
    {
        $this->worker->setForm($form);

        return $this;
    }

    public function getForm() : Form\Service\CreateAbstract
    {
        return $this->worker->getForm();
    }

    /**
     * @param Entity\Service $service
     * @return $this
     */
    public function setService(Entity\Service $service)
    {
        if (($serviceType = $service->getType()) && !$this->worker) {
            $this->setWorkerFromServiceType($serviceType->getSlug());
        }

        if (!$service->getType()) {
            $service->setType($this->worker->getServiceType());
        }

        if (!$service->getName()) {
            $serviceName = $this->serviceDomain->generateName(
                $service->getProject(),
                $this->worker->getServiceType(),
                $service->getVersion()
            );

            $service->setName($serviceName);
        }

        $this->worker->setService($service);

        return $this;
    }

    protected function getService() : Entity\Service
    {
        return $this->worker->getService();
    }

    public function getCreateParams() : array
    {
        return array_merge([
            'service'  => $this->getService(),
            'form'     => $this->getForm(),
            'networks' => $this->getCreateNetworks(),
            'ports'    => $this->getCreatePorts(),
            'secrets'  => $this->getCreateSecrets(),
            'volumes'  => $this->getCreateVolumes(),
        ], $this->worker->getCreateParams());
    }

    public function getViewParams() : array
    {
        return array_merge([
            'service'  => $this->getService(),
            'form'     => $this->getForm(),
            'networks' => $this->getViewNetworks(),
            'ports'    => $this->getViewPorts(),
            'secrets'  => $this->getViewSecrets(),
            'volumes'  => $this->getViewVolumes(),
        ], $this->worker->getViewParams());
    }

    public function create()
    {
        $this->worker->create();

        $this->createNetworks();
        $this->createPorts();
        $this->createSecrets();
        $this->createVolumes();

        $this->repo->persist($this->getService());
        $this->repo->flush();

        $this->manageChildren();
    }

    public function update()
    {
        $worker = $this->worker;

        $worker->update();

        $this->updateNetworks();
        $this->updatePorts();
        $this->updateSecrets();
        $this->updateVolumes();

        $this->repo->persist($this->getService());
        $this->repo->flush();

        $this->manageChildren();
    }

    public function delete(Entity\Service $service = null)
    {
        $service = $service ?? $this->getService();

        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);
            $this->repo->remove($meta);
        }

        $this->secretDomain->deleteAllForService($service);
        $this->volumeDomain->deleteAllForService($service);

        if ($parent = $service->getParent()) {
            $parent->removeChild($service);
            $this->repo->persist($parent);
        }

        foreach ($service->getChildren() as $child) {
            $service->removeChild($child);

            $this->delete($child);
        }

        $this->repo->remove($service);
        $this->repo->flush();
    }

    protected function manageChildren()
    {
        $worker = $this->worker;

        if (!is_a($worker, WorkerParentInterface::class)) {
            return;
        }

        $project = $this->getService()->getProject();

        /** @var WorkerParentInterface $worker $child */
        $data = $worker->manageChildren();

        foreach ($data['create'] as $childData) {
            $childServiceTypeSlug = $childData['serviceTypeSlug'];
            $childForm            = $childData['form'];

            $handler = clone $this;
            $handler->setWorkerFromServiceType($childServiceTypeSlug);

            $childService = new Entity\Service();
            $childService->setProject($project)
                ->setParent($this->getService());

            $handler->setService($childService);
            $handler->setForm($childForm);
            $handler->create();

            $this->repo->persist($this->getService());
            $this->repo->flush();
        }

        foreach ($data['update'] as $childData) {
            /** @var Entity\Service $childService */
            $childService = $childData['service'];
            $childForm    = $childData['form'];

            $handler = clone $this;
            $handler->setWorkerFromServiceType($childService->getType()->getSlug());
            $handler->setService($childService);
            $handler->setForm($childForm);
            $handler->update();

            $this->repo->persist($this->getService());
            $this->repo->flush();
        }

        /** @var Entity\Service $childService */
        foreach ($data['delete'] as $childService) {
            $handler = clone $this;
            $handler->setWorkerFromServiceType($childService->getType()->getSlug());
            $handler->setService($childService);
            $handler->delete();

            $this->repo->persist($this->getService());
            $this->repo->flush();
        }
    }

    protected function getCreateNetworks() : array
    {
        return $this->networkDomain->getForNewService(
            $this->worker->getProject(),
            $this->worker->getInternalNetworks()
        );
    }

    protected function getViewNetworks() : array
    {
        return $this->networkDomain->getForExistingService(
            $this->getService(),
            $this->worker->getInternalNetworks()
        );
    }

    protected function createNetworks()
    {
        $this->networkDomain->save($this->getService(), $this->getForm()->networks);
        $this->networkDomain->deleteEmptyNetworks($this->worker->getProject());
    }

    protected function updateNetworks()
    {
        $this->createNetworks();
    }

    protected function getCreatePorts() : Collections\ArrayCollection
    {
        $ports = new Collections\ArrayCollection();
        foreach ($this->worker->getInternalPorts() as $data) {
            $port = new Entity\ServicePort();
            $port->fromArray(['id' => uniqid()]);
            $port->setPublished($data[0])
                ->setTarget($data[1])
                ->setProtocol($data[2]);

            $ports->set($port->getId(), $port);
        }

        return $ports;
    }

    protected function getViewPorts() : iterable
    {
        return $this->getService()->getPorts();
    }

    protected function createPorts()
    {
        foreach ($this->getForm()->ports as $data) {
            $port = new Entity\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($this->getService());

            $this->repo->persist($port);
        }
    }

    protected function updatePorts()
    {
        $service = $this->getService();

        foreach ($service->getPorts() as $port) {
            $service->removePort($port);
            $this->repo->remove($port);
        }

        foreach ($this->getForm()->ports as $data) {
            $port = new Entity\ServicePort();
            $port->setPublished($data['published'])
                ->setTarget($data['target'])
                ->setProtocol($data['protocol'])
                ->setService($service);

            $this->repo->persist($port);
        }
    }

    protected function getCreateSecrets() : array
    {
        return $this->secretDomain->getForNewService(
            $this->worker->getProject(),
            $this->worker->getServiceType(),
            $this->worker->getInternalSecrets()
        );
    }

    protected function getViewSecrets() : array
    {
        return $this->secretDomain->getForExistingService(
            $this->getService(),
            $this->worker->getServiceType(),
            $this->worker->getInternalSecrets()
        );
    }

    protected function createSecrets()
    {
        $service = $this->getService();
        $form    = $this->getForm();
        $secrets = $this->getCreateSecrets();

        $this->secretDomain->save(
            $service,
            $secrets['owned']->toArray(),
            $form->secrets
        );

        $this->secretDomain->grant(
            $service,
            $form->secrets_granted
        );
    }

    protected function updateSecrets()
    {
        $service = $this->getService();
        $form    = $this->getForm();
        $secrets = $this->getViewSecrets();

        $this->secretDomain->save(
            $service,
            $secrets['owned']->toArray(),
            $form->secrets
        );

        $this->secretDomain->grant(
            $service,
            $form->secrets_granted
        );
    }

    /**
     * Returns non-persisted ServiceVolumes [name => metaName] hydrated
     * from ServiceTypeMeta data
     *
     * @return Collections\ArrayCollection[]
     */
    protected function getCreateVolumes() : array
    {
        return $this->volumeDomain->getForNewService(
            $this->worker->getProject(),
            $this->worker->getServiceType(),
            $this->worker->getInternalVolumes()
        );
    }

    /**
     * Returns persisted ServiceVolumes [name => metaName] hydrated
     * from ServiceTypeMeta data
     *
     * @return Collections\ArrayCollection[]
     */
    protected function getViewVolumes() : array
    {
        return $this->volumeDomain->getForExistingService(
            $this->getService(),
            $this->worker->getServiceType(),
            $this->worker->getInternalVolumes()
        );
    }

    protected function createVolumes()
    {
        $service = $this->getService();
        $form    = $this->getForm();
        $volumes = $this->getCreateVolumes();

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

        $this->volumeDomain->grant(
            $service,
            $form->volumes_granted
        );
    }

    protected function updateVolumes()
    {
        $service = $this->getService();
        $form    = $this->getForm();
        $volumes = $this->getViewVolumes();

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

        $this->volumeDomain->grant(
            $service,
            $form->volumes_granted
        );
    }
}
