<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PhpFpm extends WorkerAbstract implements WorkerInterface
{
    /** @var Blackfire */
    protected $blackfireWorker;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\ServiceType $serviceTypeRepo,
        Domain\Docker\Network $networkDomain,
        Domain\Docker\Secret $secretDomain,
        Domain\Docker\Volume $volume,
        Blackfire $blackfireWorker
    ) {
        parent::__construct($serviceRepo, $serviceTypeRepo, $networkDomain, $secretDomain, $volume);

        $this->blackfireWorker = $blackfireWorker;
    }

    public function getServiceType() : Entity\Docker\ServiceType
    {
        if (!$this->serviceType) {
            $this->serviceType = $this->serviceTypeRepo->findBySlug('php-fpm');
        }

        return $this->serviceType;
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
        $this->createSecrets($service, $form);
        $this->createVolumes($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($versionMeta, $service);

        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        $this->serviceRepo->save($service);

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
        $this->version = $service->getMeta('version')->getData()[0];

        $build = $service->getBuild()->getArgs();

        $phpPackagesSelected = $build['PHP_PACKAGES'];

        $phpPackagesAvailable = [];
        if ($phpVersionedPackages = $service->getType()->getMeta("packages-{$this->version}")) {
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
            'version'                => $this->version,
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

        // create or update blackfire service
        if (!empty($form->blackfire['install'])) {
            $this->createUpdateBlackfireChild($service, $form);
        }

        // delete blackfire service
        if (empty($form->blackfire['install'])) {
            $this->deleteBlackfireChild($service);
        }

        $this->addToPrivateNetworks($service, $form);
        $this->updateSecrets($service, $form);
        $this->updateVolumes($service, $form);

        $this->serviceRepo->save($service);

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

        foreach ($this->networkDomain->findByService($parent) as $network) {
            $blackfireForm->networks_join []= $network->getId();
        }

        if (!$blackfireService = $this->getBlackfireChild($parent)) {
            $blackfireType = $this->blackfireWorker->getServiceType();
            $blackfireSlug = $blackfireType->getSlug();

            $blackfireForm->name = "{$blackfireSlug}-{$form->name}";
            $blackfireForm->type = $this->serviceTypeRepo->findBySlug(
                $blackfireSlug
            );

            $blackfireService = $this->blackfireWorker->create($blackfireForm);

            $blackfireService->setParent($parent);
            $parent->addChild($blackfireService);

            foreach ($parent->getNetworks() as $network) {
                $blackfireService->addNetwork($network);
                $network->addService($blackfireService);
            }

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

        $blackfireService->setParent(null);
        $parent->removeChild($blackfireService);

        $blackfireNetworkMeta = $parent->getMeta('blackfire-network');
        foreach ($blackfireService->getNetworks() as $network) {
            $blackfireService->removeNetwork($network);
            $network->removeService($blackfireService);

            if ($network->getName() === $blackfireNetworkMeta->getData()[0]) {
                $network->removeService($parent);
                $parent->removeNetwork($network);
            }
        }

        $this->serviceRepo->save($parent);
        $this->serviceRepo->delete(
            $blackfireService,
            $blackfireNetworkMeta
        );

        $this->networkDomain->deleteEmptyNetworks($parent->getProject());
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

    protected function internalSecretsArray() : array
    {
        return [];
    }
}
