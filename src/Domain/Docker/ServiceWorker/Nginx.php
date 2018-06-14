<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Nginx extends WorkerAbstract implements WorkerInterface
{
    public function getServiceType() : Entity\Docker\ServiceType
    {
        if (!$this->serviceType) {
            $this->serviceType = $this->serviceTypeRepo->findBySlug('nginx');
        }

        return $this->serviceType;
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\NginxCreate();
    }

    /**
     * @param Form\Docker\Service\NginxCreate $form
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
            ->setDockerfile('Dockerfile')
            ->setArgs([
                'SYSTEM_PACKAGES' => array_unique($form->system_packages),
            ]);

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $this->networkDomain->addToPublicNetwork($service);
        $this->addToPrivateNetworks($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $serverNames = array_merge([$form->server_name], $form->server_alias);
        $serverNames = implode(',', $serverNames);

        $service->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', "Host:{$serverNames}");

        $vhost = [
            'server_name'   => $form->server_name,
            'server_alias'  => $form->server_alias,
            'document_root' => $form->document_root,
            'handler'       => $form->handler,
        ];

        $vhostMeta = new Entity\Docker\ServiceMeta();
        $vhostMeta->setName('vhost')
            ->setData($vhost)
            ->setService($service);

        $service->addMeta($vhostMeta);

        $this->serviceRepo->save($vhostMeta, $service);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return array_merge(parent::getCreateParams($project), [
            'handlers'      => $this->getHandlersForView($project),
            'fileHighlight' => 'nginx',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $systemPackagesSelected = $service->getBuild()->getArgs()['SYSTEM_PACKAGES'];

        $vhostMeta = $service->getMeta('vhost');

        return array_merge(parent::getViewParams($service), [
            'systemPackagesSelected' => $systemPackagesSelected,
            'vhost'                  => [
                'server_name'   => $vhostMeta->getData()['server_name'],
                'server_alias'  => $vhostMeta->getData()['server_alias'],
                'document_root' => $vhostMeta->getData()['document_root'],
                'handler'       => $vhostMeta->getData()['handler'],
            ],
            'handlers'               => $this->getHandlersForView($service->getProject()),
            'fileHighlight'          => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service           $service
     * @param Form\Docker\Service\NginxCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('Dockerfile')
            ->setArgs([
                'SYSTEM_PACKAGES' => array_unique($form->system_packages),
            ]);

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $serverNames = array_merge([$form->server_name], $form->server_alias);
        $serverNames = implode(',', $serverNames);

        $service->addLabel('traefik.frontend.rule', "Host:{$serverNames}");

        $vhost = [
            'server_name'   => $form->server_name,
            'server_alias'  => $form->server_alias,
            'document_root' => $form->document_root,
            'handler'       => $form->handler,
        ];

        $vhostMeta = $service->getMeta('vhost');
        $vhostMeta->setData($vhost);

        $this->serviceRepo->save($vhostMeta);

        $this->addToPrivateNetworks($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

        return $service;
    }

    protected function getHandlersForView(Entity\Docker\Project $project) : array
    {
        $phpFpmType     = $this->serviceTypeRepo->findBySlug('php-fpm');
        $phpFpmServices = $this->serviceRepo->findByProjectAndType(
            $project,
            $phpFpmType
        );

        $phpfpm = [];
        foreach ($phpFpmServices as $service) {
            $phpfpm []= "{$service->getName()}:9000";
        }

        $nodeJsType     = $this->serviceTypeRepo->findBySlug('node-js');
        $nodeJsServices = $this->serviceRepo->findByProjectAndType(
            $project,
            $nodeJsType
        );

        $nodejs = [];
        foreach ($nodeJsServices as $service) {
            $portMeta = $service->getMeta('port');
            $port = $portMeta->getData()[0];

            $nodejs []= "{$service->getName()}:{$port}";
        }

        return [
            'PHP-FPM' => $phpfpm,
            'Node.js' => $nodejs,
        ];
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
                'nginx-conf',
                'core-conf',
                'proxy-conf',
                'vhost-conf',
                'Dockerfile',
            ],
            'other' => [
                'root',
            ],
        ];
    }

    protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array {
        return [];
    }
}
