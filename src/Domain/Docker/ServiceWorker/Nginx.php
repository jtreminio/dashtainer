<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class Nginx extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'nginx';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
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

        $publicNetwork = $this->networkRepo->getPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($publicNetwork);

        $this->serviceRepo->save($service, $publicNetwork);

        $this->addToPrivateNetworks($service, $form);

        $serverNames = array_merge([$form->server_name], $form->server_alias);
        $serverNames = implode(',', $serverNames);

        $service->addLabel('traefik.backend', $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', "Host:{$serverNames}");

        $vhost = [
            'server_name'   => $form->server_name,
            'server_alias'  => $form->server_alias,
            'document_root' => $form->document_root,
            'fcgi_handler'  => $form->fcgi_handler,
        ];

        $vhostMeta = new Entity\Docker\ServiceMeta();
        $vhostMeta->setName('vhost')
            ->setData($vhost)
            ->setService($service);

        $service->addMeta($vhostMeta);

        $this->serviceRepo->save($vhostMeta, $service);

        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $nginxConf = new Entity\Docker\ServiceVolume();
        $nginxConf->setName('nginx.conf')
            ->setSource("\$PWD/{$service->getSlug()}/nginx.conf")
            ->setTarget('/etc/nginx/nginx.conf')
            ->setData($form->file['nginx.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $coreConf = new Entity\Docker\ServiceVolume();
        $coreConf->setName('core.conf')
            ->setSource("\$PWD/{$service->getSlug()}/core.conf")
            ->setTarget('/etc/nginx/conf.d/core.conf')
            ->setData($form->file['core.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $proxyConf = new Entity\Docker\ServiceVolume();
        $proxyConf->setName('proxy.conf')
            ->setSource("\$PWD/{$service->getSlug()}/proxy.conf")
            ->setTarget('/etc/nginx/conf.d/proxy.conf')
            ->setData($form->file['proxy.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $vhostConf = new Entity\Docker\ServiceVolume();
        $vhostConf->setName('vhost.conf')
            ->setSource("\$PWD/{$service->getSlug()}/vhost.conf")
            ->setTarget('/etc/nginx/sites-available/default')
            ->setData($form->vhost_conf ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $service->addVolume($dockerfile)
            ->addVolume($nginxConf)
            ->addVolume($coreConf)
            ->addVolume($proxyConf)
            ->addVolume($vhostConf);

        $this->serviceRepo->save(
            $dockerfile, $nginxConf, $coreConf, $proxyConf, $vhostConf, $service
        );

        $this->projectFilesCreate($service, $form);

        $this->userFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        $phpFpmType = $this->serviceTypeRepo->findBySlug('php-fpm');

        $phpFpmServices = $this->serviceRepo->findByProjectAndType(
            $project,
            $phpFpmType
        );

        return [
            'fcgi_handlers' => [
                'phpfpm' => $phpFpmServices,
            ],
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $systemPackagesSelected = $service->getBuild()->getArgs()['SYSTEM_PACKAGES'];

        $dockerfile  = $service->getVolume('Dockerfile');
        $nginxConf   = $service->getVolume('nginx.conf');
        $coreConf    = $service->getVolume('core.conf');
        $proxyConf   = $service->getVolume('proxy.conf');
        $vhostConf   = $service->getVolume('vhost.conf');
        $userFiles   = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        $vhostMeta = $service->getMeta('vhost');

        $phpFpmType = $this->serviceTypeRepo->findBySlug('php-fpm');

        $phpFpmServices = $this->serviceRepo->findByProjectAndType(
            $service->getProject(),
            $phpFpmType
        );

        return [
            'projectFiles'           => $this->projectFilesViewParams($service),
            'systemPackagesSelected' => $systemPackagesSelected,
            'configFiles'            => [
                'Dockerfile' => $dockerfile,
                'nginx.conf' => $nginxConf,
                'core.conf'  => $coreConf,
                'proxy.conf' => $proxyConf,
            ],
            'userFiles'              => $userFiles,
            'vhost'                  => [
                'server_name'   => $vhostMeta->getData()['server_name'],
                'server_alias'  => $vhostMeta->getData()['server_alias'],
                'document_root' => $vhostMeta->getData()['document_root'],
                'fcgi_handler'  => $vhostMeta->getData()['fcgi_handler'],
            ],
            'vhost_conf'             => $vhostConf,
            'fcgi_handlers'          => [
                'phpfpm' => $phpFpmServices,
            ],
        ];
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

        $this->addToPrivateNetworks($service, $form);

        $this->serviceRepo->save($service);

        $serverNames = array_merge([$form->server_name], $form->server_alias);
        $serverNames = implode(',', $serverNames);

        $service->addLabel('traefik.frontend.rule', "Host:{$serverNames}");

        $vhost = [
            'server_name'   => $form->server_name,
            'server_alias'  => $form->server_alias,
            'document_root' => $form->document_root,
            'fcgi_handler'  => $form->fcgi_handler,
        ];

        $vhostMeta = $service->getMeta('vhost');
        $vhostMeta->setData($vhost);

        $this->serviceRepo->save($vhostMeta);

        $dockerfile = $service->getVolume('Dockerfile');
        $dockerfile->setData($form->file['Dockerfile'] ?? '');

        $nginxConf = $service->getVolume('nginx.conf');
        $nginxConf->setData($form->file['nginx.conf'] ?? '');

        $coreConf = $service->getVolume('core.conf');
        $coreConf->setData($form->file['core.conf'] ?? '');

        $proxyConf = $service->getVolume('proxy.conf');
        $proxyConf->setData($form->file['proxy.conf'] ?? '');

        $vhostConf = $service->getVolume('vhost.conf');
        $vhostConf->setData($form->vhost_conf ?? '');

        $this->serviceRepo->save(
            $dockerfile, $nginxConf, $coreConf, $proxyConf, $vhostConf
        );

        $this->projectFilesUpdate($service, $form);

        $this->userFilesUpdate($service, $form);

        return $service;
    }
}
