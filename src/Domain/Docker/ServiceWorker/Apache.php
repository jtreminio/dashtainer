<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Apache extends WorkerAbstract implements WorkerInterface
{
    /** @var Repository\Docker\Network */
    protected $networkRepo;

    /** @var Repository\Docker\ServiceType */
    protected $serviceTypeRepo;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\Network $networkRepo,
        Repository\Docker\ServiceType $serviceTypeRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->networkRepo = $networkRepo;

        $this->serviceTypeRepo = $serviceTypeRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'apache';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\ApacheCreate();
    }

    /**
     * @param Form\Docker\Service\ApacheCreate $form
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
                'SYSTEM_PACKAGES'       => array_unique($form->system_packages),
                'APACHE_MODULES_ENABLE' => array_unique($form->enabled_modules),
                'APACHE_MODULES_DISABLE'=> array_unique($form->disabled_modules),
            ]);

        $service->setBuild($build);

        $publicNetwork = $this->networkRepo->getPrimaryPublicNetwork(
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

        $apache2Conf = new Entity\Docker\ServiceVolume();
        $apache2Conf->setName('apache2.conf')
            ->setSource("\$PWD/{$service->getSlug()}/apache2.conf")
            ->setTarget('/etc/apache2/apache2.conf')
            ->setData($form->file['apache2.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('apacheconf')
            ->setService($service);

        $portsConf = new Entity\Docker\ServiceVolume();
        $portsConf->setName('ports.conf')
            ->setSource("\$PWD/{$service->getSlug()}/ports.conf")
            ->setTarget('/etc/apache2/ports.conf')
            ->setData($form->file['ports.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('apacheconf')
            ->setService($service);

        $vhostConf = new Entity\Docker\ServiceVolume();
        $vhostConf->setName('vhost.conf')
            ->setSource("\$PWD/{$service->getSlug()}/vhost.conf")
            ->setTarget('/etc/apache2/sites-enabled/000-default.conf')
            ->setData($form->vhost_conf ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('apacheconf')
            ->setService($service);

        $service->addVolume($dockerfile)
            ->addVolume($apache2Conf)
            ->addVolume($portsConf)
            ->addVolume($vhostConf);

        $this->serviceRepo->save(
            $dockerfile, $apache2Conf, $portsConf, $vhostConf, $service
        );

        $this->projectFilesCreate($service, $form);

        $this->customFilesCreate($service, $form);

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
        $args = $service->getBuild()->getArgs();

        $apacheModulesEnable    = $args['APACHE_MODULES_ENABLE'];
        $apacheModulesDisable   = $args['APACHE_MODULES_DISABLE'];
        $systemPackagesSelected = $args['SYSTEM_PACKAGES'];

        $availableApacheModules = [];

        $apacheModules          = $service->getType()->getMeta('modules');
        $availableApacheModules += $apacheModules->getData()['default'];
        $availableApacheModules += $apacheModules->getData()['available'];

        $dockerfile  = $service->getVolume('Dockerfile');
        $apache2Conf = $service->getVolume('apache2.conf');
        $portsConf   = $service->getVolume('ports.conf');
        $vhostConf   = $service->getVolume('vhost.conf');
        $customFiles = $service->getVolumesByOwner(
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
            'apacheModulesEnable'    => $apacheModulesEnable,
            'apacheModulesDisable'   => $apacheModulesDisable,
            'availableApacheModules' => $availableApacheModules,
            'systemPackagesSelected' => $systemPackagesSelected,
            'configFiles'            => [
                'Dockerfile'   => $dockerfile,
                'apache2.conf' => $apache2Conf,
                'ports.conf'   => $portsConf,
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
     * @param Entity\Docker\Service            $service
     * @param Form\Docker\Service\ApacheCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('DockerFile')
            ->setArgs([
                'SYSTEM_PACKAGES'       => array_unique($form->system_packages),
                'APACHE_MODULES_ENABLE' => array_unique($form->enabled_modules),
                'APACHE_MODULES_DISABLE'=> array_unique($form->disabled_modules),
            ]);

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

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

        $apache2Conf = $service->getVolume('apache2.conf');
        $apache2Conf->setData($form->file['apache2.conf'] ?? '');

        $portsConf   = $service->getVolume('ports.conf');
        $portsConf->setData($form->file['ports.conf'] ?? '');

        $vhostConf   = $service->getVolume('vhost.conf');
        $vhostConf->setData($form->vhost_conf);

        $this->serviceRepo->save($dockerfile, $apache2Conf, $portsConf, $vhostConf);

        $this->projectFilesUpdate($service, $form);

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
