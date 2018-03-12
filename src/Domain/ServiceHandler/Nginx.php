<?php

namespace Dashtainer\Domain\ServiceHandler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Nginx extends HandlerAbstract implements CrudInterface
{
    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var Repository\DockerServiceTypeRepository */
    protected $serviceTypeRepo;

    public function __construct(
        Repository\DockerProjectRepository $projectRepo,
        Repository\DockerServiceRepository $serviceRepo,
        Repository\DockerServiceTypeRepository $serviceTypeRepo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->networkRepo     = $networkRepo;
        $this->serviceRepo     = $serviceRepo;
        $this->serviceTypeRepo = $serviceTypeRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'nginx';
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\Service\CreateAbstract {
        return new Form\Service\NginxCreate();
    }

    /**
     * @param Form\Service\NginxCreate $form
     * @return Entity\DockerService
     */
    public function create($form) : Entity\DockerService
    {
        $service = new Entity\DockerService();
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

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $publicNetwork = $this->networkRepo->getPrimaryPublicNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork);

        $this->serviceRepo->save($service, $privateNetwork, $publicNetwork);

        $serverNames = array_merge([$form->server_name], $form->server_alias);
        $serverNames = implode(',', $serverNames);

        $service->addLabel('traefik.backend', $privateNetwork->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', "Host:{$serverNames}");

        $vhost = [
            'server_name'   => $form->server_name,
            'server_alias'  => $form->server_alias,
            'document_root' => $form->document_root,
            'fcgi_handler'  => $form->fcgi_handler,
        ];

        $vhostMeta = new Entity\DockerServiceMeta();
        $vhostMeta->setName('vhost')
            ->setData($vhost)
            ->setService($service);

        $service->addMeta($vhostMeta);

        $this->serviceRepo->save($vhostMeta, $service);

        $dockerfile = new Entity\DockerServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $nginxConf = new Entity\DockerServiceVolume();
        $nginxConf->setName('nginx.conf')
            ->setSource("\$PWD/{$service->getSlug()}/nginx.conf")
            ->setTarget('/etc/nginx/nginx.conf')
            ->setData($form->file['nginx.conf'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $coreConf = new Entity\DockerServiceVolume();
        $coreConf->setName('core.conf')
            ->setSource("\$PWD/{$service->getSlug()}/core.conf")
            ->setTarget('/etc/nginx/conf.d/core.conf')
            ->setData($form->file['core.conf'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $proxyConf = new Entity\DockerServiceVolume();
        $proxyConf->setName('proxy.conf')
            ->setSource("\$PWD/{$service->getSlug()}/proxy.conf")
            ->setTarget('/etc/nginx/conf.d/proxy.conf')
            ->setData($form->file['proxy.conf'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
            ->setHighlight('nginx')
            ->setService($service);

        $vhostConf = new Entity\DockerServiceVolume();
        $vhostConf->setName('vhost.conf')
            ->setSource("\$PWD/{$service->getSlug()}/vhost.conf")
            ->setTarget('/etc/nginx/sites-available/default')
            ->setData($form->vhost_conf ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
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

        $this->customFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\DockerProject $project) : array
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

    public function getViewParams(Entity\DockerService $service) : array
    {
        $systemPackagesSelected = $service->getBuild()->getArgs()['SYSTEM_PACKAGES'];

        $dockerfile  = $service->getVolume('Dockerfile');
        $nginxConf   = $service->getVolume('nginx.conf');
        $coreConf    = $service->getVolume('core.conf');
        $proxyConf   = $service->getVolume('proxy.conf');
        $vhostConf   = $service->getVolume('vhost.conf');
        $customFiles = $service->getVolumesByOwner(Entity\DockerServiceVolume::OWNER_USER);

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
            'customFiles'            => $customFiles,
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
     * @param Entity\DockerService     $service
     * @param Form\Service\NginxCreate $form
     * @return Entity\DockerService
     */
    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService {
        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('DockerFile')
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

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
