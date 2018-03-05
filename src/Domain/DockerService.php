<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerService
{
    /** @var Repository\DockerServiceRepository */
    protected $repo;

    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    public function __construct(
        Repository\DockerServiceRepository $repo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->repo        = $repo;
        $this->networkRepo = $networkRepo;
    }

    public function createServiceFromForm(
        Form\DockerServiceCreateAbstract $form
    ) : Entity\DockerService {
        if (is_a($form, Form\DockerServiceCreate\PhpFpm::class)) {
            /** @var Form\DockerServiceCreate\PhpFpm $form */
            return $this->createPhpFpmServiceFromForm($form);
        }

        return null;
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : ?Form\DockerServiceCreateAbstract {
        if ($serviceType->getSlug() == 'php-fpm') {
            return new Form\DockerServiceCreate\PhpFpm();
        }

        return null;
    }

    public function generateServiceName(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType,
        string $version = null
    ) : string {
        $services = $this->repo->findBy([
            'project' => $project,
            'type'    => $serviceType,
        ]);

        $version  = $version ? "-{$version}" : '';
        $hostname = Util\Strings::hostname("{$serviceType->getSlug()}{$version}");

        if (empty($services)) {
            return $hostname;
        }

        $usedNames = [];
        foreach ($services as $service) {
            $usedNames []= $service->getName();
        }

        for ($i = 1; $i <= count($usedNames); $i++) {
            $name = "{$hostname}-{$i}";

            if (!in_array($name, $usedNames)) {
                return $name;
            }
        }

        return "{$hostname}-" . uniqid();
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        if ($service->getType()->getSlug() == 'php-fpm') {
            return $this->getViewParamsPhpFpm($service);
        }

        return [];
    }

    protected function getViewParamsPhpFpm(Entity\DockerService $service) : array
    {
        $version    = $service->getMeta('version')->getData()[0];
        $projectVol = $service->getVolume('project_directory');

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

        $phpIni     = $service->getVolume('fpm-php.ini');
        $fpmIni     = $service->getVolume('fpm.conf');
        $fpmPoolIni = $service->getVolume('fpm_pool.conf');

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
            'install'      => $service->getBuild()->getArgs()['BLACKFIRE_INSTALL'],
            'server_id'    => '', // @todo grab from separate blackfire service
            'server_token' => '', // @todo grab from separate blackfire service
        ];

        return [
            'version'                => $version,
            'projectVol'             => $projectVol,
            'phpPackagesSelected'    => $phpPackagesSelected,
            'availablePhpPackages'   => $availablePhpPackages,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'iniFiles'               => [
                'php.ini'       => $phpIni,
                'fpm.conf'      => $fpmIni,
                'fpm_pool.conf' => $fpmPoolIni,
            ],
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
        ];
    }

    protected function createPhpFpmServiceFromForm(
        Form\DockerServiceCreate\PhpFpm $form
    ) : Entity\DockerService {
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
            ->setDockerfile('DockerFile')
            ->setArgs([
                'SYSTEM_PACKAGES'   => array_unique($form->system_packages),
                'PHP_PACKAGES'      => array_unique($phpPackages),
                'PEAR_PACKAGES'     => array_unique($form->pear_packages),
                'PECL_PACKAGES'     => array_unique($form->pecl_packages),
                'COMPOSER_INSTALL'  => $form->composer['install'] ?? false,
                'BLACKFIRE_INSTALL' => $form->blackfire['install'] ?? false,
            ]);

        $service->setBuild($build);

        if ($form->blackfire['install'] ?? false) {
            $service->setEnvironments([
                'BLACKFIRE_HOST' => "blackfire-{$service->getSlug()}",
            ]);
        }

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->repo->save($service, $privateNetwork);

        $versionMeta = new Entity\DockerServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->repo->save($versionMeta, $service);

        $cliIni = new Entity\DockerServiceVolume();
        $cliIni->setName('cli-php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_custom.ini")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['php.ini'] ?? '')
            ->setIsRemovable(false)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmIni = new Entity\DockerServiceVolume();
        $fpmIni->setName('fpm-php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_custom.ini")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['php.ini'] ?? '')
            ->setIsRemovable(false)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmConf = new Entity\DockerServiceVolume();
        $fpmConf->setName('fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['fpm.conf'])
            ->setIsRemovable(false)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmPoolConf = new Entity\DockerServiceVolume();
        $fpmPoolConf->setName('fpm_pool.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm_pool.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/pool.d/www.conf")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['fpm_pool.conf'])
            ->setIsRemovable(false)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $directory = new Entity\DockerServiceVolume();
        $directory->setName('project_directory')
            ->setSource($form->directory)
            ->setTarget('/var/www')
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_CACHED)
            ->setIsRemovable(false)
            ->setType(Entity\DockerServiceVolume::TYPE_DIR)
            ->setService($service);

        $service->addVolume($cliIni)
            ->addVolume($fpmIni)
            ->addVolume($fpmConf)
            ->addVolume($fpmPoolConf)
            ->addVolume($directory);

        $this->repo->save($cliIni, $fpmIni, $fpmConf, $fpmPoolConf, $directory, $service);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = new Entity\DockerServiceVolume();
            $xdebugIni->setName('xdebug.ini')
                ->setSource("\$PWD/{$service->getSlug()}/xdebug.ini")
                ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_xdebug.ini")
                ->setPropogation('delegated')
                ->setData($form->xdebug['ini'])
                ->setIsRemovable(false)
                ->setType('file')
                ->setService($service);

            $service->addVolume($xdebugIni);

            $this->repo->save($xdebugIni, $service);
        }

        // @todo add blackfire service if needed

        return $service;
    }
}
