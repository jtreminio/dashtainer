<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;

class PhpFpm extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'php-fpm';

    /** @var Blackfire */
    protected $blackfireWorker;

    /**
     * @required
     * @param Blackfire $blackfireWorker
     */
    public function setBlackfireWorker(Blackfire $blackfireWorker)
    {
        $this->blackfireWorker = $blackfireWorker;
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
            ->setProject($form->project)
            ->setVersion($form->version);

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

        $this->createNetworks($service, $form);
        $this->createPorts($service, $form);
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $this->serviceRepo->persist($service);

        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        $serviceType = $this->getServiceType();

        return array_merge(parent::getCreateParams($project), [
            'phpVersionedPackages' => $serviceType->getMeta("packages-{$this->version}"),
            'phpGeneralPackages'   => $serviceType->getMeta('packages-general'),
            'fileHighlight'        => 'ini',
        ]);
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version = $service->getVersion();

        $build = $service->getBuild()->getArgs();

        $phpPackagesSelected = $build['PHP_PACKAGES'];

        $phpPackagesAvailable = [];
        if ($phpVersionedPackages = $service->getType()->getMeta("packages-{$version}")) {
            $phpPackagesAvailable += $phpVersionedPackages->getData()['default'];
            $phpPackagesAvailable += $phpVersionedPackages->getData()['available'];
        }

        if ($phpGeneralPackages = $service->getType()->getMeta('packages-general')) {
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

        if ($blackfireService = $this->getBlackfireChild($service)) {
            $bfEnv = $blackfireService->getEnvironments();

            $blackfire['install']      = 1;
            $blackfire['server_id']    = $bfEnv['BLACKFIRE_SERVER_ID'];
            $blackfire['server_token'] = $bfEnv['BLACKFIRE_SERVER_TOKEN'];
        }

        return array_merge(parent::getViewParams($service), [
            'phpPackagesSelected'    => $phpPackagesSelected,
            'phpPackagesAvailable'   => $phpPackagesAvailable,
            'pearPackagesSelected'   => $pearPackagesSelected,
            'peclPackagesSelected'   => $peclPackagesSelected,
            'systemPackagesSelected' => $systemPackagesSelected,
            'composer'               => $composer,
            'xdebug'                 => $xdebug,
            'blackfire'              => $blackfire,
            'fileHighlight'          => 'ini',
        ]);
    }

    /**
     * @param Entity\Docker\Service            $service
     * @param Form\Docker\Service\PhpFpmCreate $form
     */
    public function update(Entity\Docker\Service $service, $form)
    {
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

        $this->serviceRepo->persist($service);

        // create or update blackfire service
        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        // delete blackfire service
        if (empty($form->blackfire['install'])) {
            $this->deleteBlackfireChild($service);
        }

        $this->updateNetworks($service, $form);
        $this->updatePorts($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->persist($service);
        $this->serviceRepo->flush();
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
            $blackfireType = $this->blackfireWorker->getServiceType();
            $blackfireSlug = $blackfireType->getSlug();

            $blackfireForm->name = "{$blackfireSlug}-{$form->name}";
            $blackfireForm->type = $blackfireType;

            $blackfireService = $this->blackfireWorker->create($blackfireForm);

            $blackfireService->setParent($parent);
            $parent->addChild($blackfireService);

            $this->serviceRepo->persist($blackfireService, $parent);

            return $blackfireService;
        }

        $this->blackfireWorker->update($blackfireService, $blackfireForm);

        return $blackfireService;
    }

    protected function getBlackfireChild(
        Entity\Docker\Service $parent
    ) : ?Entity\Docker\Service {
        return $this->serviceRepo->findChildByType(
            $parent,
            $this->blackfireWorker->getServiceType()
        );
    }

    protected function deleteBlackfireChild(Entity\Docker\Service $parent)
    {
        $blackfireService = $this->serviceRepo->findChildByType(
            $parent,
            $this->blackfireWorker->getServiceType()
        );

        if (!$blackfireService) {
            return;
        }

        $parent->removeChild($blackfireService);

        $this->delete($blackfireService);
    }

    protected function internalNetworksArray() : array
    {
        return [];
    }

    protected function internalPortsArray() : array
    {
        return [];
    }

    protected function internalSecretsArray() : array
    {
        return [];
    }

    protected function internalVolumesArray() : array
    {
        return [
            'files' => [
                "php-ini-{$this->version}",
                "php-ini-cli-{$this->version}",
                "fpm-conf-{$this->version}",
                "xdebug-ini-{$this->version}",
                "xdebug-ini-cli-{$this->version}",
                'fpm-bin',
                'xdebug-bin',
                "Dockerfile-{$this->version}",
            ],
            'other' => [
                'root',
            ],
        ];
    }
}
