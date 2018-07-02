<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Repository\Docker as Repository;

class PhpFpm
    extends WorkerAbstract
    implements WorkerParentInterface, WorkerServiceRepoInterface
{
    public const SERVICE_TYPE_SLUG = 'php-fpm';

    /** @var Form\Service\PhpFpmCreate */
    protected $form;

    /** @var Repository\Service */
    protected $repo;

    public function setRepo(Repository\Service $repo)
    {
        $this->repo = $repo;
    }

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\PhpFpmCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setVersion($this->form->version);

        $phpPackages = $this->form->php_packages;

        if ($this->form->xdebug['install'] ?? false) {
            $phpPackages []= 'php-xdebug';
        }

        $args = [
            'SYSTEM_PACKAGES'  => array_unique($this->form->system_packages),
            'PHP_PACKAGES'     => array_unique($phpPackages),
            'PEAR_PACKAGES'    => array_unique($this->form->pear_packages),
            'PECL_PACKAGES'    => array_unique($this->form->pecl_packages),
            'COMPOSER_INSTALL' => $this->form->composer['install'] ?? 0,
        ];

        if ($this->form->blackfire['install'] ?? false) {
            $args ['BLACKFIRE_SERVER']= "blackfire-{$this->service->getSlug()}";
        }

        $build = $this->service->getBuild();
        $build->setContext("./{$this->service->getSlug()}")
            ->setDockerfile('Dockerfile')
            ->setArgs($args);

        $this->service->setBuild($build);
    }

    public function update()
    {
        $phpPackages = $this->form->php_packages;

        if ($this->form->xdebug['install'] ?? false) {
            $phpPackages []= 'php-xdebug';
        } else {
            $phpPackages = array_diff($phpPackages, ['php-xdebug']);
        }

        $args = [
            'SYSTEM_PACKAGES'  => array_unique($this->form->system_packages),
            'PHP_PACKAGES'     => array_unique($phpPackages),
            'PEAR_PACKAGES'    => array_unique($this->form->pear_packages),
            'PECL_PACKAGES'    => array_unique($this->form->pecl_packages),
            'COMPOSER_INSTALL' => $this->form->composer['install'] ?? 0,
        ];

        if ($this->form->blackfire['install'] ?? false) {
            $args ['BLACKFIRE_SERVER']= "blackfire-{$this->service->getSlug()}";
        }

        $build = $this->service->getBuild();
        $build->setArgs($args);

        $this->service->setBuild($build);
    }

    public function getCreateParams() : array
    {
        $serviceType = $this->getServiceType();

        $phpVersionedPackagesMeta = $serviceType->getMeta("packages-{$this->service->getVersion()}");

        return [
            'phpPackagesSelected'    => $phpVersionedPackagesMeta->getData()['default'],
            'phpPackagesAvailable'   => $phpVersionedPackagesMeta->getData()['available'],
            'pearPackagesSelected'   => [],
            'peclPackagesSelected'   => [],
            'systemPackagesSelected' => [],
            'composer'               => ['install' => true],
            'xdebug'                 => ['install' => false],
            'blackfire'              => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight'          => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        $version = $this->service->getVersion();

        $build = $this->service->getBuild()->getArgs();

        $phpPackagesSelected = $build['PHP_PACKAGES'];

        $phpPackagesAvailable = [];
        if ($phpVersionedPackages = $this->service->getType()->getMeta("packages-{$version}")) {
            $phpPackagesAvailable += $phpVersionedPackages->getData()['default'];
            $phpPackagesAvailable += $phpVersionedPackages->getData()['available'];
        }

        if ($phpGeneralPackages = $this->service->getType()->getMeta('packages-general')) {
            $phpPackagesAvailable += $phpGeneralPackages->getData()['default'];
            $phpPackagesAvailable += $phpGeneralPackages->getData()['available'];
        }

        $phpPackagesAvailable = array_diff($phpPackagesAvailable, $phpPackagesSelected);

        $pearPackagesSelected   = $build['PEAR_PACKAGES'];
        $peclPackagesSelected   = $build['PECL_PACKAGES'];
        $systemPackagesSelected = $build['SYSTEM_PACKAGES'];

        $composer = [
            'install' => $build['COMPOSER_INSTALL'],
        ];

        $xdebug = [
            'install' => in_array('php-xdebug', $phpPackagesSelected),
        ];

        $blackfire = [
            'install'      => 0,
            'server_id'    => '',
            'server_token' => '',
        ];

        if ($blackfireService = $this->getBlackfireChild()) {
            $bfEnv = $blackfireService->getEnvironments();

            $blackfire['install']      = 1;
            $blackfire['server_id']    = $bfEnv['BLACKFIRE_SERVER_ID'];
            $blackfire['server_token'] = $bfEnv['BLACKFIRE_SERVER_TOKEN'];
        }

        return [
            'phpPackagesSelected'    => $phpPackagesSelected,
            'phpPackagesAvailable'   => $phpPackagesAvailable,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
            'fileHighlight'          => 'ini',
        ];
    }

    public function getInternalVolumes() : array
    {
        $version = $this->getService()->getVersion();

        return [
            'files' => [
                "php-ini-{$version}",
                "php-ini-cli-{$version}",
                "fpm-conf-{$version}",
                "xdebug-ini-{$version}",
                "xdebug-ini-cli-{$version}",
                'fpm-bin',
                'xdebug-bin',
                "dockerfile-{$version}",
            ],
            'other' => [
                'root',
            ],
        ];
    }

    public function manageChildren() : array
    {
        $data = [
            'create' => [],
            'update' => [],
            'delete' => [],
        ];

        $existingBlackfireChild = $this->getBlackfireChild();

        // existing, delete
        if (empty($this->form->blackfire['install']) && $existingBlackfireChild) {
            $data['delete'] []= $existingBlackfireChild;
        }

        // existing, update
        if (!empty($this->form->blackfire['install']) && $existingBlackfireChild) {
            $blackfireForm = Blackfire::getFormInstance();
            $blackfireForm->fromArray($this->form->blackfire);

            foreach ($this->service->getNetworks() as $network) {
                $blackfireForm->networks [$network->getId()]= [
                    'id'   => $network->getId(),
                    'name' => $network->getName(),
                ];
            }

            $data['update'] []= [
                'service' => $existingBlackfireChild,
                'form'    => $blackfireForm,
            ];
        }

        // not existing, create
        if (!empty($this->form->blackfire['install']) && !$existingBlackfireChild) {
            $blackfireForm = Blackfire::getFormInstance();
            $blackfireForm->fromArray($this->form->blackfire);
            $blackfireForm->name = "blackfire-{$this->service->getSlug()}";

            foreach ($this->service->getNetworks() as $network) {
                $blackfireForm->networks [$network->getId()]= [
                    'id'   => $network->getId(),
                    'name' => $network->getName(),
                ];
            }

            $data['create'] []= [
                'serviceTypeSlug' => Blackfire::SERVICE_TYPE_SLUG,
                'form'            => $blackfireForm,
            ];
        }

        return $data;
    }

    protected function getBlackfireChild() : ?Entity\Service
    {
        return $this->repo->findChildByTypeName(
            $this->service,
            Blackfire::SERVICE_TYPE_SLUG
        );
    }
}
