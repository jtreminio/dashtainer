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

    public function deleteService(Entity\DockerService $service)
    {
        if ($service->getType()->getSlug() == 'php-fpm') {
            $this->deletePhpFpmService($service);
        }
    }

    public function updateServiceFromForm(
        Form\DockerServiceCreateAbstract $form,
        Entity\DockerService $service
    ) : Entity\DockerService {
        if (is_a($form, Form\DockerServiceCreate\PhpFpm::class)) {
            /** @var Form\DockerServiceCreate\PhpFpm $form */
            return $this->updatePhpFpmServiceFromForm($form, $service);
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

        $userFiles = $service->getVolumesByOwner(Entity\DockerServiceVolume::OWNER_USER);

        return [
            'version'                => $version,
            'projectVol'             => $projectVol,
            'phpPackagesSelected'    => $phpPackagesSelected,
            'availablePhpPackages'   => $availablePhpPackages,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'configFiles'            => [
                'php.ini'       => $phpIni,
                'fpm.conf'      => $fpmIni,
                'fpm_pool.conf' => $fpmPoolIni,
            ],
            'userFiles'              => $userFiles,
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
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmIni = new Entity\DockerServiceVolume();
        $fpmIni->setName('fpm-php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_custom.ini")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['php.ini'] ?? '')
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmConf = new Entity\DockerServiceVolume();
        $fpmConf->setName('fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['fpm.conf'])
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmPoolConf = new Entity\DockerServiceVolume();
        $fpmPoolConf->setName('fpm_pool.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm_pool.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/pool.d/www.conf")
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setData($form->file['fpm_pool.conf'])
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $directory = new Entity\DockerServiceVolume();
        $directory->setName('project_directory')
            ->setSource($form->directory)
            ->setTarget('/var/www')
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_CACHED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
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
                ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
                ->setData($form->xdebug['ini'])
                ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
                ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                ->setService($service);

            $service->addVolume($xdebugIni);

            $this->repo->save($xdebugIni, $service);
        }

        $files = [];
        foreach ($form->additional_file as $fileConfig) {
            $file = new Entity\DockerServiceVolume();
            $file->setName($fileConfig['filename'])
                ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                ->setTarget($fileConfig['target'])
                ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
                ->setData($fileConfig['data'])
                ->setOwner(Entity\DockerServiceVolume::OWNER_USER)
                ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                ->setService($service);

            $service->addVolume($file);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->repo->save($service, ...$files);
        }

        // @todo add blackfire service if needed

        return $service;
    }

    protected function updatePhpFpmServiceFromForm(
        Form\DockerServiceCreate\PhpFpm $form,
        Entity\DockerService $service
    ) : Entity\DockerService {
        $phpPackages = $form->php_packages;

        if ($form->xdebug['install'] ?? false) {
            $phpPackages []= 'php-xdebug';
        } else {
            $phpPackages = array_diff($phpPackages, ['php-xdebug']);
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

        $this->repo->save($service);

        $cliIni = $service->getVolume('cli-php.ini');
        $cliIni->setData($form->file['php.ini'] ?? '');

        $fpmIni = $service->getVolume('fpm-php.ini');
        $fpmIni->setData($form->file['php.ini'] ?? '');

        $fpmConf = $service->getVolume('fpm.conf');
        $fpmConf->setData($form->file['fpm.conf']);

        $fpmPoolConf = $service->getVolume('fpm_pool.conf');
        $fpmPoolConf->setData($form->file['fpm_pool.conf']);

        $directory = $service->getVolume('project_directory');
        $directory->setSource($form->directory);

        $this->repo->save($cliIni, $fpmIni, $fpmConf, $fpmPoolConf, $directory);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = $service->getVolume('xdebug.ini');
            $xdebugIni->setData($form->xdebug['ini']);

            $this->repo->save($xdebugIni);
        }

        $existingUserFiles = $service->getVolumesByOwner(
            Entity\DockerServiceVolume::OWNER_USER
        );
        $files = [];

        foreach ($form->additional_file as $id => $fileConfig) {
            if (empty($existingUserFiles[$id])) {
                $file = new Entity\DockerServiceVolume();
                $file->setName($fileConfig['filename'])
                    ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                    ->setTarget($fileConfig['target'])
                    ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
                    ->setData($fileConfig['data'])
                    ->setOwner(Entity\DockerServiceVolume::OWNER_USER)
                    ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                    ->setService($service);

                $service->addVolume($file);

                $files []= $file;

                continue;
            }

            /** @var Entity\DockerServiceVolume $file */
            $file = $existingUserFiles[$id];
            unset($existingUserFiles[$id]);

            $file->setName($fileConfig['filename'])
                ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                ->setTarget($fileConfig['target'])
                ->setData($fileConfig['data']);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->repo->save($service, ...$files);
        }

        foreach ($existingUserFiles as $file) {
            $service->removeVolume($file);
            $this->repo->delete($file);
            $this->repo->save($service);
        }

        // @todo add blackfire service if needed

        return $service;
    }

    protected function deletePhpFpmService(Entity\DockerService $service)
    {
        $metas = [];
        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);

            $metas []= $meta;
        }

        $volumes = [];
        foreach ($service->getVolumes() as $volume) {
            $service->removeVolume($volume);

            $volumes []= $volume;
        }

        $this->repo->delete(...$metas, ...$volumes);
        $this->repo->delete($service);

        // @todo delete blackfire service if needed
    }
}
