<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class PhpFpm
    extends WorkerAbstract
    implements WorkerParentInterface, WorkerServiceRepoInterface
{
    use WorkerServiceRepoTrait;

    public const SERVICE_TYPE_SLUG = 'php-fpm';

    /** @var Form\Service\PhpFpmCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\PhpFpmCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setVersion($this->form->version);

        if ($this->form->xdebug['install'] ?? false) {
            $this->form->system_packages []= 'php-xdebug';
        }

        $args = [
            'SYSTEM_PACKAGES'  => array_values(array_unique($this->form->system_packages)),
            'PEAR_PACKAGES'    => array_values(array_unique($this->form->pear_packages)),
            'PECL_PACKAGES'    => array_values(array_unique($this->form->pecl_packages)),
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
        if ($this->form->xdebug['install'] ?? false) {
            $this->form->system_packages []= 'php-xdebug';
        } else {
            $this->form->system_packages = array_diff(
                $this->form->system_packages,
                ['php-xdebug']
            );
        }

        $args = [
            'SYSTEM_PACKAGES'  => array_values(array_unique($this->form->system_packages)),
            'PEAR_PACKAGES'    => array_values(array_unique($this->form->pear_packages)),
            'PECL_PACKAGES'    => array_values(array_unique($this->form->pecl_packages)),
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
        $version     = $this->service->getVersion();

        $phpVersionedPackagesMeta = $serviceType->getMeta("packages-{$version}");
        $phpGeneralPackagesMeta   = $serviceType->getMeta('packages-general');

        $systemPackagesSelected  = array_merge(
            $phpVersionedPackagesMeta->getData()['default'],
            $phpGeneralPackagesMeta->getData()['default']
        );
        $systemPackagesAvailable = array_merge(
            $phpVersionedPackagesMeta->getData()['available'],
            $phpGeneralPackagesMeta->getData()['available']
        );

        $systemPackagesSelected  = array_unique($systemPackagesSelected);
        $systemPackagesAvailable = array_unique($systemPackagesAvailable);

        return [
            'systemPackagesSelected'  => $systemPackagesSelected,
            'systemPackagesAvailable' => $systemPackagesAvailable,
            'pearPackagesSelected'    => [],
            'peclPackagesSelected'    => [],
            'composer'                => ['install' => true],
            'xdebug'                  => ['install' => false],
            'blackfire'               => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight'           => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        $serviceType = $this->getServiceType();
        $version     = $this->service->getVersion();
        $build       = $this->service->getBuild()->getArgs();

        $phpVersionedPackagesMeta = $serviceType->getMeta("packages-{$version}");
        $phpGeneralPackagesMeta   = $serviceType->getMeta('packages-general');

        $systemPackagesSelected  = $build['SYSTEM_PACKAGES'];
        $systemPackagesAvailable = array_merge(
            $phpVersionedPackagesMeta->getData()['default'],
            $phpGeneralPackagesMeta->getData()['default'],
            $phpVersionedPackagesMeta->getData()['available'],
            $phpGeneralPackagesMeta->getData()['available']
        );

        $systemPackagesAvailable = array_diff($systemPackagesAvailable, $systemPackagesSelected);
        $systemPackagesSelected  = array_unique($systemPackagesSelected);
        $systemPackagesAvailable = array_unique($systemPackagesAvailable);

        $composer = [
            'install' => $build['COMPOSER_INSTALL'],
        ];

        $xdebug = [
            'install' => in_array('php-xdebug', $systemPackagesSelected),
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
            'systemPackagesSelected'  => array_values($systemPackagesSelected),
            'systemPackagesAvailable' => array_values($systemPackagesAvailable),
            'pearPackagesSelected'    => array_values($build['PEAR_PACKAGES']),
            'peclPackagesSelected'    => array_values($build['PECL_PACKAGES']),
            'composer'                => $composer,
            'xdebug'                  => $xdebug,
            'blackfire'               => $blackfire,
            'fileHighlight'           => 'ini',
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
