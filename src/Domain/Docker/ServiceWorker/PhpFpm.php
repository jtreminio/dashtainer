<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PhpFpm extends WorkerAbstract implements WorkerInterface
{
    /** @var Blackfire */
    protected $blackfire;

    /** @var Repository\Docker\Network */
    protected $repoDockNetwork;

    /** @var Repository\Docker\ServiceType */
    protected $repoDockServiceType;

    public function __construct(
        Repository\Docker\Service $repoDockService,
        Repository\Docker\Network $repoDockNetwork,
        Repository\Docker\ServiceType $repoDockServiceType,
        Blackfire $blackfire
    ) {
        $this->repoDockService     = $repoDockService;
        $this->repoDockNetwork     = $repoDockNetwork;
        $this->repoDockServiceType = $repoDockServiceType;

        $this->blackfire = $blackfire;
    }

    public function getServiceTypeSlug() : string
    {
        return 'php-fpm';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\PhpFpmCreate();
    }

    /**
     * @param Form\Docker\Service\PhpFpmCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $phpPackages = $form->php_packages;

        if ($form->xdebug['install'] ?? false) {
            $phpPackages []= 'php-xdebug';
        }

        $build = $service->getBuild();
        $build->setContext("./{$service->getSlug()}")
            ->setDockerfile('Dockerfile')
            ->setArgs([
                'SYSTEM_PACKAGES'   => array_unique($form->system_packages),
                'PHP_PACKAGES'      => array_unique($phpPackages),
                'PEAR_PACKAGES'     => array_unique($form->pear_packages),
                'PECL_PACKAGES'     => array_unique($form->pecl_packages),
                'COMPOSER_INSTALL'  => $form->composer['install'] ?? 0,
                'BLACKFIRE_INSTALL' => $form->blackfire['install'] ?? 0,
            ]);

        $service->setBuild($build);

        $privateNetwork = $this->repoDockNetwork->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->repoDockService->save($service, $privateNetwork);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->repoDockService->save($versionMeta, $service);

        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $phpIni = new Entity\Docker\ServiceVolume();
        $phpIni->setName('php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/mods-available/zzzz_custom.ini")
            ->setData($form->file['php.ini'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('ini')
            ->setService($service);

        $fpmConf = new Entity\Docker\ServiceVolume();
        $fpmConf->setName('php-fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/php-fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setData($form->file['php-fpm.conf'])
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('ini')
            ->setService($service);

        $service->addVolume($dockerfile)
            ->addVolume($phpIni)
            ->addVolume($fpmConf);

        $this->repoDockService->save($dockerfile, $phpIni, $fpmConf, $service);

        $this->projectFilesCreate($service, $form);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = new Entity\Docker\ServiceVolume();
            $xdebugIni->setName('xdebug.ini')
                ->setSource("\$PWD/{$service->getSlug()}/xdebug.ini")
                ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_xdebug.ini")
                ->setData($form->xdebug['ini'])
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                ->setService($service);

            $service->addVolume($xdebugIni);

            $this->repoDockService->save($xdebugIni, $service);
        }

        $this->customFilesCreate($service, $form);

        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version = $service->getMeta('version')->getData()[0];

        $phpPackagesSelected = $service->getBuild()->getArgs()['PHP_PACKAGES'];

        $availablePhpPackages = [];
        if ($phpVersionedPackages = $service->getType()->getMeta("packages-${version}")) {
            $availablePhpPackages += $phpVersionedPackages->getData()['default'];
            $availablePhpPackages += $phpVersionedPackages->getData()['available'];
        }

        if ($phpGeneralPackages = $service->getType()->getMeta('packages-general')) {
            $availablePhpPackages += $phpGeneralPackages->getData()['default'];
            $availablePhpPackages += $phpGeneralPackages->getData()['available'];
        }

        $availablePhpPackages = array_diff($availablePhpPackages, $phpPackagesSelected);

        $pearPackagesSelected   = $service->getBuild()->getArgs()['PEAR_PACKAGES'];
        $peclPackagesSelected   = $service->getBuild()->getArgs()['PECL_PACKAGES'];
        $systemPackagesSelected = $service->getBuild()->getArgs()['SYSTEM_PACKAGES'];

        $dockerfile = $service->getVolume('Dockerfile');
        $phpIni     = $service->getVolume('php.ini');
        $fpmConf    = $service->getVolume('php-fpm.conf');

        $composer = [
            'install' => $service->getBuild()->getArgs()['COMPOSER_INSTALL'],
        ];

        if ($xdebugVol = $service->getVolume('xdebug.ini')) {
            $xdebugIni = $xdebugVol->getData();
        } else {
            $xdebugIni = $service->getType()->getMeta('ini-xdebug')->getData()[0];
        }

        $xdebug = [
            'install' => in_array('php-xdebug', $phpPackagesSelected),
            'ini'     => $xdebugIni,
        ];

        $blackfire = [
            'install'      => 0,
            'server_id'    => '',
            'server_token' => '',
        ];

        if ($blackfireService = $this->getBlackfireChild($service)) {
            $bfEnv = $blackfireService->getEnvironments();

            $blackfire['install']      = 1;
            $blackfire['server_id']    = $bfEnv['BLACKFIRE_SERVER_ID'];
            $blackfire['server_token'] = $bfEnv['BLACKFIRE_SERVER_TOKEN'];
        }

        $customFiles = $service->getVolumesByOwner(Entity\Docker\ServiceVolume::OWNER_USER);

        return [
            'version'                => $version,
            'projectFiles'           => $this->projectFilesViewParams($service),
            'phpPackagesSelected'    => $phpPackagesSelected,
            'availablePhpPackages'   => $availablePhpPackages,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'configFiles'            => [
                'Dockerfile'   => $dockerfile,
                'php.ini'      => $phpIni,
                'php-fpm.conf' => $fpmConf,
            ],
            'customFiles'            => $customFiles,
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
        ];
    }

    /**
     * @param Entity\Docker\Service     $service
     * @param Form\Docker\Service\PhpFpmCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $phpPackages = $form->php_packages;

        if ($form->xdebug['install'] ?? false) {
            $phpPackages []= 'php-xdebug';
        } else {
            $phpPackages = array_diff($phpPackages, ['php-xdebug']);
        }

        $build = $service->getBuild();
        $build->setArgs([
            'SYSTEM_PACKAGES'   => array_unique($form->system_packages),
            'PHP_PACKAGES'      => array_unique($phpPackages),
            'PEAR_PACKAGES'     => array_unique($form->pear_packages),
            'PECL_PACKAGES'     => array_unique($form->pecl_packages),
            'COMPOSER_INSTALL'  => $form->composer['install'] ?? 0,
            'BLACKFIRE_INSTALL' => $form->blackfire['install'] ?? 0,
        ]);

        $service->setBuild($build);

        $this->repoDockService->save($service);

        $dockerfile = $service->getVolume('Dockerfile');
        $dockerfile->setData($form->file['Dockerfile'] ?? '');

        $phpIni = $service->getVolume('php.ini');
        $phpIni->setData($form->file['php.ini'] ?? '');

        $fpmConf = $service->getVolume('php-fpm.conf');
        $fpmConf->setData($form->file['php-fpm.conf']);

        $this->repoDockService->save($dockerfile, $phpIni, $fpmConf);

        $this->projectFilesUpdate($service, $form);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = $service->getVolume('xdebug.ini');
            $xdebugIni->setData($form->xdebug['ini']);

            $this->repoDockService->save($xdebugIni);
        }

        // create or update blackfire service
        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        // delete blackfire service
        if (empty($form->blackfire['install'])) {
            $this->deleteBlackfireChild($service);
        }

        $this->customFilesUpdate($service, $form);

        return $service;
    }

    protected function createUpdateBlackfireChild(
        Entity\Docker\Service $parent,
        Form\Docker\Service\PhpFpmCreate $form
    ) : Entity\Docker\Service {
        /** @var Form\Docker\Service\BlackfireCreate $blackfireForm */
        $blackfireForm = $this->blackfire->getCreateForm();

        $blackfireForm->fromArray($form->blackfire);

        if (!$blackfireService = $this->getBlackfireChild($parent)) {
            $blackfireSlug = $this->blackfire->getServiceTypeSlug();

            $blackfireForm->name    = "{$blackfireSlug}-{$form->name}";
            $blackfireForm->project = $form->project;
            $blackfireForm->type    = $this->repoDockServiceType->findBySlug(
                $blackfireSlug
            );

            $blackfireService = $this->blackfire->create($blackfireForm);

            $blackfireService->setParent($parent);
            $parent->addChild($blackfireService);

            $this->repoDockService->save($blackfireService, $parent);

            return $blackfireService;
        }

        $this->blackfire->update($blackfireService, $blackfireForm);

        return $blackfireService;
    }

    protected function getBlackfireChild(
        Entity\Docker\Service $parent
    ) : ?Entity\Docker\Service {
        $blackfireSlug = $this->blackfire->getServiceTypeSlug();
        $blackfireType = $this->repoDockServiceType->findBySlug($blackfireSlug);

        return $this->repoDockService->findChildByType(
            $parent,
            $blackfireType
        );
    }

    protected function deleteBlackfireChild(Entity\Docker\Service $parent) {
        $blackfireSlug = $this->blackfire->getServiceTypeSlug();
        $blackfireType = $this->repoDockServiceType->findBySlug($blackfireSlug);

        $blackfireService = $this->repoDockService->findChildByType(
            $parent,
            $blackfireType
        );

        if (!$blackfireService) {
            return;
        }

        $blackfireService->setParent(null);
        $parent->removeChild($blackfireService);

        $this->repoDockService->save($parent);
        $this->repoDockService->delete($blackfireService);
    }
}
