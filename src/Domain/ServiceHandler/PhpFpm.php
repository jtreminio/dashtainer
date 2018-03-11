<?php

namespace Dashtainer\Domain\ServiceHandler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PhpFpm extends HandlerAbstract implements CrudInterface
{
    /** @var Blackfire */
    protected $blackfireHandler;

    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var Repository\DockerServiceTypeRepository */
    protected $serviceTypeRepo;

    public function __construct(
        Repository\DockerServiceRepository $serviceRepo,
        Repository\DockerNetworkRepository $networkRepo,
        Repository\DockerServiceTypeRepository $serviceTypeRepo,
        Blackfire $blackfireHandler
    ) {
        $this->serviceRepo     = $serviceRepo;
        $this->networkRepo     = $networkRepo;
        $this->serviceTypeRepo = $serviceTypeRepo;

        $this->blackfireHandler = $blackfireHandler;
    }

    public function getServiceTypeSlug() : string
    {
        return 'php-fpm';
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\Service\CreateAbstract {
        return new Form\Service\PhpFpmCreate();
    }

    /**
     * @param Form\Service\PhpFpmCreate $form
     * @return Entity\DockerService
     */
    public function create($form) : Entity\DockerService
    {
        $service = new Entity\DockerService();
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
                'COMPOSER_INSTALL'  => $form->composer['install'] ?? false,
                'BLACKFIRE_INSTALL' => $form->blackfire['install'] ?? false,
            ]);

        $service->setBuild($build);

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->serviceRepo->save($service, $privateNetwork);

        $versionMeta = new Entity\DockerServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($versionMeta, $service);

        $phpIni = new Entity\DockerServiceVolume();
        $phpIni->setName('php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/mods-available/zzzz_custom.ini")
            ->setData($form->file['php.ini'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmConf = new Entity\DockerServiceVolume();
        $fpmConf->setName('php-fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/php-fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setData($form->file['php-fpm.conf'])
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmPoolConf = new Entity\DockerServiceVolume();
        $fpmPoolConf->setName('php-fpm_pool.conf')
            ->setSource("\$PWD/{$service->getSlug()}/php-fpm_pool.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/pool.d/www.conf")
            ->setData($form->file['php-fpm_pool.conf'])
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $service->addVolume($phpIni)
            ->addVolume($fpmConf)
            ->addVolume($fpmPoolConf);

        $this->serviceRepo->save($phpIni, $fpmConf, $fpmPoolConf, $service);

        $this->projectFilesCreate($service, $form);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = new Entity\DockerServiceVolume();
            $xdebugIni->setName('xdebug.ini')
                ->setSource("\$PWD/{$service->getSlug()}/xdebug.ini")
                ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_xdebug.ini")
                ->setData($form->xdebug['ini'])
                ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
                ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                ->setService($service);

            $service->addVolume($xdebugIni);

            $this->serviceRepo->save($xdebugIni, $service);
        }

        $this->customFilesCreate($service, $form);

        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        return $service;
    }

    public function getCreateParams(Entity\DockerProject $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\DockerService $service) : array
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

        $phpIni     = $service->getVolume('php.ini');
        $fpmConf    = $service->getVolume('php-fpm.conf');
        $fpmPoolIni = $service->getVolume('php-fpm_pool.conf');

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
            'install'      => false,
            'server_id'    => '',
            'server_token' => '',
        ];

        if ($blackfireService = $this->getBlackfireChild($service)) {
            $bfEnv = $blackfireService->getEnvironments();

            $blackfire['install']      = true;
            $blackfire['server_id']    = $bfEnv['BLACKFIRE_SERVER_ID'];
            $blackfire['server_token'] = $bfEnv['BLACKFIRE_SERVER_TOKEN'];
        }

        $customFiles = $service->getVolumesByOwner(Entity\DockerServiceVolume::OWNER_USER);

        return [
            'version'                => $version,
            'projectFiles'           => $this->projectFilesViewParams($service),
            'phpPackagesSelected'    => $phpPackagesSelected,
            'availablePhpPackages'   => $availablePhpPackages,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'configFiles'            => [
                'php.ini'           => $phpIni,
                'php-fpm.conf'      => $fpmConf,
                'php-fpm_pool.conf' => $fpmPoolIni,
            ],
            'customFiles'            => $customFiles,
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
        ];
    }

    /**
     * @param Entity\DockerService      $service
     * @param Form\Service\PhpFpmCreate $form
     * @return Entity\DockerService
     */
    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService {
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
            'COMPOSER_INSTALL'  => $form->composer['install'] ?? false,
            'BLACKFIRE_INSTALL' => $form->blackfire['install'] ?? false,
        ]);

        $service->setBuild($build);

        $this->serviceRepo->save($service);

        $phpIni = $service->getVolume('php.ini');
        $phpIni->setData($form->file['php.ini'] ?? '');

        $fpmConf = $service->getVolume('php-fpm.conf');
        $fpmConf->setData($form->file['php-fpm.conf']);

        $fpmPoolConf = $service->getVolume('php-fpm_pool.conf');
        $fpmPoolConf->setData($form->file['php-fpm_pool.conf']);

        $this->serviceRepo->save($phpIni, $fpmConf, $fpmPoolConf);

        $this->projectFilesUpdate($service, $form);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = $service->getVolume('xdebug.ini');
            $xdebugIni->setData($form->xdebug['ini']);

            $this->serviceRepo->save($xdebugIni);
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
        Entity\DockerService $parent,
        Form\Service\PhpFpmCreate $form
    ) : Entity\DockerService {
        /** @var Form\Service\BlackfireCreate $blackfireForm */
        $blackfireForm = $this->blackfireHandler->getCreateForm();

        $blackfireForm->fromArray($form->blackfire);

        if (!$blackfireService = $this->getBlackfireChild($parent)) {
            $blackfireSlug = $this->blackfireHandler->getServiceTypeSlug();

            $blackfireForm->name    = "{$blackfireSlug}-{$form->name}";
            $blackfireForm->project = $form->project;
            $blackfireForm->type    = $this->serviceTypeRepo->findBySlug($blackfireSlug);

            $blackfireService = $this->blackfireHandler->create($blackfireForm);

            $blackfireService->setParent($parent);
            $parent->addChild($blackfireService);

            $this->serviceRepo->save($blackfireService, $parent);

            return $blackfireService;
        }

        $this->blackfireHandler->update($blackfireService, $blackfireForm);

        return $blackfireService;
    }

    protected function getBlackfireChild(
        Entity\DockerService $parent
    ) : ?Entity\DockerService {
        $blackfireSlug = $this->blackfireHandler->getServiceTypeSlug();
        $blackfireType = $this->serviceTypeRepo->findBySlug($blackfireSlug);

        return $this->serviceRepo->findChildByType(
            $parent,
            $blackfireType
        );
    }

    protected function deleteBlackfireChild(Entity\DockerService $parent) {
        $blackfireSlug = $this->blackfireHandler->getServiceTypeSlug();
        $blackfireType = $this->serviceTypeRepo->findBySlug($blackfireSlug);

        $blackfire = $this->serviceRepo->findChildByType(
            $parent,
            $blackfireType
        );

        if (!$blackfire) {
            return;
        }

        $blackfire->setParent(null);
        $parent->removeChild($blackfire);

        $this->serviceRepo->save($parent);
        $this->serviceRepo->delete($blackfire);
    }
}
