<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class NodeJs extends WorkerAbstract implements WorkerInterface
{
    public function getServiceType() : Entity\Docker\ServiceType
    {
        if (!$this->serviceType) {
            $this->serviceType = $this->serviceTypeRepo->findBySlug('node-js');
        }

        return $this->serviceType;
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\NodeJsCreate();
    }

    /**
     * @param Form\Docker\Service\NodeJsCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $service->setImage("node:{$form->version}")
            ->setExpose([$form->port])
            ->setCommand([$form->command])
            ->setWorkingDir('/var/www');

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('port')
            ->setData([$form->port])
            ->setService($service);

        $service->addMeta($portMeta);

        $this->serviceRepo->save($versionMeta, $portMeta, $service);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'fileHighlight' => 'ini',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version  = $service->getMeta('version')->getData()[0];
        $portMeta = $service->getMeta('port');

        return array_merge(parent::getViewParams($service), [
            'version'       => $version,
            'port'          => $portMeta->getData()[0],
            'command'       => $service->getCommand(),
            'fileHighlight' => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service            $service
     * @param Form\Docker\Service\NodeJsCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setExpose([$form->port])
            ->setCommand([$form->command]);

        $portMeta = $service->getMeta('port');
        $portMeta->setData([$form->port]);

        $this->serviceRepo->save($portMeta);

        $this->addToPrivateNetworks($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

        return $service;
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
            ],
            'other' => [
                'root',
            ],
        ];
    }

    protected function internalSecretsArray() : array
    {
        return [];
    }
}
