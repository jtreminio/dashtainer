<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Beanstalkd extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'beanstalkd';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\BeanstalkdCreate();
    }

    /**
     * @param Form\Docker\Service\BeanstalkdCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('Dockerfile');

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $service->addVolume($dockerfile);

        $this->serviceRepo->save($dockerfile, $service);

        $this->createDatastore($service, $form, '/var/lib/beanstalkd/binlog');

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $datastore = $service->getMeta('datastore')->getData()[0];

        $dockerfile  = $service->getVolume('Dockerfile');

        return [
            'datastore'   => $datastore,
            'configFiles' => [
                'Dockerfile' => $dockerfile,
            ],
        ];
    }

    /**
     * @param Entity\Docker\Service                $service
     * @param Form\Docker\Service\BeanstalkdCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $dockerfile = $service->getVolume('Dockerfile');
        $dockerfile->setData($form->file['Dockerfile'] ?? '');

        $this->serviceRepo->save($dockerfile);

        $this->updateDatastore($service, $form);

        return $service;
    }
}
