<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PhpFpm extends WorkerAbstract implements WorkerInterface
{
    /** @var Blackfire */
    protected $blackfireWorker;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\Network $networkRepo,
        Repository\Docker\Secret $secretRepo,
        Repository\Docker\ServiceType $serviceTypeRepo,
        Blackfire $blackfireWorker
    ) {
        parent::__construct($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);

        $this->blackfireWorker = $blackfireWorker;
    }

    public function getServiceTypeSlug() : string
    {
        return 'php-fpm';
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
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

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($versionMeta, $service);

        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->setName('Dockerfile')
            ->setSource("\$PWD/{$service->getSlug()}/Dockerfile")
            ->setData($form->system_file['Dockerfile'] ?? '')
            ->setConsistency(null)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('docker')
            ->setService($service);

        $phpIni = new Entity\Docker\ServiceVolume();
        $phpIni->setName('php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_custom.ini")
            ->setData($form->system_file['php.ini'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('ini')
            ->setService($service);

        $phpCliIni = new Entity\Docker\ServiceVolume();
        $phpCliIni->setName('php-cli.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php-cli.ini")
            ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_custom.ini")
            ->setData($form->system_file['php.ini'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('ini')
            ->setService($service);

        $fpmConf = new Entity\Docker\ServiceVolume();
        $fpmConf->setName('php-fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/php-fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setData($form->system_file['php-fpm.conf'])
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setHighlight('ini')
            ->setService($service);

        $fpmStartupMeta = $service->getType()->getMeta('php-fpm-startup');

        $fpmStartup = new Entity\Docker\ServiceVolume();
        $fpmStartup->setName('php-fpm-startup')
            ->setSource("\$PWD/{$service->getSlug()}/php-fpm-startup")
            ->setData($fpmStartupMeta->getData()[0])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $xdebugBinMeta = $service->getType()->getMeta('xdebug-bin');

        $xdebugBin = new Entity\Docker\ServiceVolume();
        $xdebugBin->setName('xdebug')
            ->setSource("\$PWD/{$service->getSlug()}/xdebug")
            ->setData($xdebugBinMeta->getData()[0])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($dockerfile)
            ->addVolume($phpIni)
            ->addVolume($phpCliIni)
            ->addVolume($fpmConf)
            ->addVolume($fpmStartup)
            ->addVolume($xdebugBin);

        $this->serviceRepo->save(
            $dockerfile, $phpIni, $phpCliIni, $fpmConf, $fpmStartup, $xdebugBin, $service
        );

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

            $xdebugCliIni = new Entity\Docker\ServiceVolume();
            $xdebugCliIni->setName('xdebug-cli.ini')
                ->setSource("\$PWD/{$service->getSlug()}/xdebug-cli.ini")
                ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_xdebug.ini")
                ->setData($form->xdebug['cli_ini'])
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                ->setService($service);

            $service->addVolume($xdebugIni)
                ->addVolume($xdebugCliIni);

            $this->serviceRepo->save($xdebugIni, $xdebugCliIni, $service);
        }

        $this->userFilesCreate($service, $form);

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

        $phpPackagesAvailable = [];
        if ($phpVersionedPackages = $service->getType()->getMeta("packages-${version}")) {
            $phpPackagesAvailable += $phpVersionedPackages->getData()['default'];
            $phpPackagesAvailable += $phpVersionedPackages->getData()['available'];
        }

        if ($phpGeneralPackages = $service->getType()->getMeta('packages-general')) {
            $phpPackagesAvailable += $phpGeneralPackages->getData()['default'];
            $phpPackagesAvailable += $phpGeneralPackages->getData()['available'];
        }

        $phpPackagesAvailable = array_diff($phpPackagesAvailable, $phpPackagesSelected);

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

        if ($xdebugCliVol = $service->getVolume('xdebug-cli.ini')) {
            $xdebugCliIni = $xdebugCliVol->getData();
        } else {
            $xdebugCliIni = $service->getType()->getMeta('ini-xdebug-cli')->getData()[0];
        }

        $xdebug = [
            'install' => in_array('php-xdebug', $phpPackagesSelected),
            'ini'     => $xdebugIni,
            'cli_ini' => $xdebugCliIni,
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

        $userFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'version'                => $version,
            'projectFiles'           => $this->projectFilesViewParams($service),
            'phpPackagesSelected'    => $phpPackagesSelected,
            'phpPackagesAvailable'   => $phpPackagesAvailable,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'systemFiles'            => [
                'Dockerfile'   => $dockerfile,
                'php.ini'      => $phpIni,
                'php-fpm.conf' => $fpmConf,
            ],
            'userFiles'              => $userFiles,
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
        ];
    }

    /**
     * @param Entity\Docker\Service            $service
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

        $this->serviceRepo->save($service);

        $this->addToPrivateNetworks($service, $form);

        $dockerfile = $service->getVolume('Dockerfile');
        $dockerfile->setData($form->system_file['Dockerfile'] ?? '');

        if (!$phpIni = $service->getVolume('php.ini')) {
            $phpIni = new Entity\Docker\ServiceVolume();
            $phpIni->setName('php.ini')
                ->setSource("\$PWD/{$service->getSlug()}/php.ini")
                ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_custom.ini")
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                ->setHighlight('ini')
                ->setService($service);

            $service->addVolume($phpIni);
        }

        if (!$phpCliIni = $service->getVolume('php-cli.ini')) {
            $phpCliIni = new Entity\Docker\ServiceVolume();
            $phpCliIni->setName('php-cli.ini')
                ->setSource("\$PWD/{$service->getSlug()}/php-cli.ini")
                ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_custom.ini")
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                ->setHighlight('ini')
                ->setService($service);

            $service->addVolume($phpCliIni);
        }

        $phpIni->setData($form->system_file['php.ini'] ?? '');
        $phpCliIni->setData($form->system_file['php.ini'] ?? '');

        $fpmConf = $service->getVolume('php-fpm.conf');
        $fpmConf->setData($form->system_file['php-fpm.conf']);

        $this->serviceRepo->save($dockerfile, $phpIni, $phpCliIni, $fpmConf);

        $this->projectFilesUpdate($service, $form);

        if ($form->xdebug['install'] ?? false) {
            if (!$xdebugIni = $service->getVolume('xdebug.ini')) {
                $xdebugIni = new Entity\Docker\ServiceVolume();
                $xdebugIni->setName('xdebug.ini')
                    ->setSource("\$PWD/{$service->getSlug()}/xdebug.ini")
                    ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_xdebug.ini")
                    ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                    ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                    ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                    ->setService($service);

                $service->addVolume($xdebugIni);
            }

            if (!$xdebugCliIni = $service->getVolume('xdebug-cli.ini')) {
                $xdebugCliIni = new Entity\Docker\ServiceVolume();
                $xdebugCliIni->setName('xdebug-cli.ini')
                    ->setSource("\$PWD/{$service->getSlug()}/xdebug-cli.ini")
                    ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_xdebug.ini")
                    ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                    ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                    ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                    ->setService($service);

                $service->addVolume($xdebugCliIni);
            }

            $xdebugIni->setData($form->xdebug['ini']);
            $xdebugCliIni->setData($form->xdebug['cli_ini']);

            $this->serviceRepo->save($xdebugIni, $xdebugCliIni);
        }

        // create or update blackfire service
        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        // delete blackfire service
        if (empty($form->blackfire['install'])) {
            $this->deleteBlackfireChild($service);
        }

        $this->userFilesUpdate($service, $form);

        return $service;
    }

    protected function createUpdateBlackfireChild(
        Entity\Docker\Service $parent,
        Form\Docker\Service\PhpFpmCreate $form
    ) : Entity\Docker\Service {
        /** @var Form\Docker\Service\BlackfireCreate $blackfireForm */
        $blackfireForm = $this->blackfireWorker->getCreateForm();

        $blackfireForm->fromArray($form->blackfire);
        $blackfireForm->project  = $form->project;
        $blackfireForm->networks = $form->networks;

        if (!$blackfireService = $this->getBlackfireChild($parent)) {
            $blackfireSlug = $this->blackfireWorker->getServiceTypeSlug();

            $blackfireForm->name = "{$blackfireSlug}-{$form->name}";
            $blackfireForm->type = $this->serviceTypeRepo->findBySlug(
                $blackfireSlug
            );

            $blackfireService = $this->blackfireWorker->create($blackfireForm);

            $blackfireService->setParent($parent);
            $parent->addChild($blackfireService);

            $this->serviceRepo->save($blackfireService, $parent);

            $networkName = "{$parent->getName()}-blackfire";
            $network = new Entity\Docker\Network();
            $network->setName($networkName)
                ->setProject($parent->getProject())
                ->setIsEditable(false)
                ->addService($parent)
                ->addService($blackfireService);

            $parent->addNetwork($network);
            $blackfireService->addNetwork($network);

            $blackfireNetwork = new Entity\Docker\ServiceMeta();
            $blackfireNetwork->setName('blackfire-network')
                ->setData([$networkName])
                ->setService($parent);

            $parent->addMeta($blackfireNetwork);

            $this->serviceRepo->save($network, $blackfireService, $blackfireNetwork, $parent);

            return $blackfireService;
        }

        $this->blackfireWorker->update($blackfireService, $blackfireForm);

        return $blackfireService;
    }

    protected function getBlackfireChild(
        Entity\Docker\Service $parent
    ) : ?Entity\Docker\Service {
        $blackfireSlug = $this->blackfireWorker->getServiceTypeSlug();
        $blackfireType = $this->serviceTypeRepo->findBySlug($blackfireSlug);

        return $this->serviceRepo->findChildByType(
            $parent,
            $blackfireType
        );
    }

    protected function deleteBlackfireChild(Entity\Docker\Service $parent) {
        $blackfireSlug = $this->blackfireWorker->getServiceTypeSlug();
        $blackfireType = $this->serviceTypeRepo->findBySlug($blackfireSlug);

        $blackfireService = $this->serviceRepo->findChildByType(
            $parent,
            $blackfireType
        );

        if (!$blackfireService) {
            return;
        }

        $blackfireService->setParent(null);
        $parent->removeChild($blackfireService);

        $blackfireNetworkMeta = $parent->getMeta('blackfire-network');
        $blackfireNetwork = $this->networkRepo->findOneBy([
            'project' => $parent->getProject(),
            'name'    => $blackfireNetworkMeta->getData()[0],
        ]);

        $parent->removeMeta($blackfireNetworkMeta);
        $blackfireNetwork->removeService($parent);
        $parent->removeNetwork($blackfireNetwork);

        $blackfireNetwork->removeService($blackfireService);
        $blackfireService->removeNetwork($blackfireNetwork);

        $this->serviceRepo->save($parent);
        $this->serviceRepo->delete(
            $blackfireService,
            $blackfireNetworkMeta,
            $blackfireNetwork
        );
    }
}
