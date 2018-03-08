<?php

namespace Dashtainer\Domain\Handler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PhpFpm implements CrudInterface
{
    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var Repository\DockerServiceRepository */
    protected $serviceRepo;

    public function __construct(
        Repository\DockerServiceRepository $serviceRepo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->networkRepo = $networkRepo;
    }

    public function getCreateFormClass() : string
    {
        return Form\DockerServiceCreate\PhpFpm::class;
    }

    public function getServiceTypeSlug() : string
    {
        return 'php-fpm';
    }

    /**
     * @param Form\DockerServiceCreate\PhpFpm $form
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

        $this->serviceRepo->save($service, $privateNetwork);

        $versionMeta = new Entity\DockerServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($versionMeta, $service);

        $cliIni = new Entity\DockerServiceVolume();
        $cliIni->setName('cli-php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/cli/conf.d/zzzz_custom.ini")
            ->setData($form->file['php.ini'] ?? '')
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmIni = new Entity\DockerServiceVolume();
        $fpmIni->setName('fpm-php.ini')
            ->setSource("\$PWD/{$service->getSlug()}/php.ini")
            ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_custom.ini")
            ->setData($form->file['php.ini'] ?? '')
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmConf = new Entity\DockerServiceVolume();
        $fpmConf->setName('fpm.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/php-fpm.conf")
            ->setData($form->file['fpm.conf'])
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $fpmPoolConf = new Entity\DockerServiceVolume();
        $fpmPoolConf->setName('fpm_pool.conf')
            ->setSource("\$PWD/{$service->getSlug()}/fpm_pool.conf")
            ->setTarget("/etc/php/{$form->version}/fpm/pool.d/www.conf")
            ->setData($form->file['fpm_pool.conf'])
            ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
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

        $this->serviceRepo->save(
            $cliIni, $fpmIni, $fpmConf, $fpmPoolConf, $directory, $service
        );

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = new Entity\DockerServiceVolume();
            $xdebugIni->setName('xdebug.ini')
                ->setSource("\$PWD/{$service->getSlug()}/xdebug.ini")
                ->setTarget("/etc/php/{$form->version}/fpm/conf.d/zzzz_xdebug.ini")
                ->setData($form->xdebug['ini'])
                ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
                ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
                ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                ->setService($service);

            $service->addVolume($xdebugIni);

            $this->serviceRepo->save($xdebugIni, $service);
        }

        $files = [];
        foreach ($form->additional_file as $fileConfig) {
            $file = new Entity\DockerServiceVolume();
            $file->setName($fileConfig['filename'])
                ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                ->setTarget($fileConfig['target'])
                ->setData($fileConfig['data'])
                ->setPropogation(Entity\DockerServiceVolume::PROPOGATION_DELEGATED)
                ->setOwner(Entity\DockerServiceVolume::OWNER_USER)
                ->setType(Entity\DockerServiceVolume::TYPE_FILE)
                ->setService($service);

            $service->addVolume($file);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->serviceRepo->save($service, ...$files);
        }

        // @todo add blackfire service if needed

        return $service;
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\DockerServiceCreateAbstract {
        return new Form\DockerServiceCreate\PhpFpm();
    }

    public function getViewParams(Entity\DockerService $service) : array
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

    /**
     * @param Entity\DockerService            $service
     * @param Form\DockerServiceCreate\PhpFpm $form
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

        $this->serviceRepo->save($service);

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

        $this->serviceRepo->save($cliIni, $fpmIni, $fpmConf, $fpmPoolConf, $directory);

        if ($form->xdebug['install'] ?? false) {
            $xdebugIni = $service->getVolume('xdebug.ini');
            $xdebugIni->setData($form->xdebug['ini']);

            $this->serviceRepo->save($xdebugIni);
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
            $this->serviceRepo->save($service, ...$files);
        }

        foreach ($existingUserFiles as $file) {
            $service->removeVolume($file);
            $this->serviceRepo->delete($file);
            $this->serviceRepo->save($service);
        }

        // @todo add blackfire service if needed

        return $service;
    }

    public function delete(Entity\DockerService $service)
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

        $this->serviceRepo->delete(...$metas, ...$volumes);
        $this->serviceRepo->delete($service);

        // @todo delete blackfire service if needed
    }
}
