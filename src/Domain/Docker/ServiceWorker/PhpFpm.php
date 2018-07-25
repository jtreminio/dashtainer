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

        $env = [];
        $ini = [];
        foreach ($this->form->ini as $row) {
            $env[$row['name']] = $row['value'];
            $ini []= $row['name'];
        }

        if ($this->form->xdebug) {
            $env['PHP_INI_SCAN_DIR'] = ':/etc/php/xdebug';
            $ini []= 'PHP_INI_SCAN_DIR';
        }

        $iniMeta = new Entity\ServiceMeta();
        $iniMeta->setName('ini')
            ->setData($ini)
            ->setService($this->service);

        $xdebugMeta = new Entity\ServiceMeta();
        $xdebugMeta->setName('xdebug')
            ->setData(['install' => $this->form->xdebug])
            ->setService($this->service);

        $args = [];
        if ($this->form->blackfire['install'] ?? false) {
            $args ['BLACKFIRE_SERVER']= "blackfire-{$this->service->getSlug()}";
        }

        $build = $this->service->getBuild();
        $build->setContext("./{$this->service->getSlug()}")
            ->setDockerfile('Dockerfile')
            ->setArgs($args);

        $this->service->setBuild($build)
            ->setEnvironments($env);
    }

    public function update()
    {
        $iniMeta    = $this->service->getMeta('ini');
        $xdebugMeta = $this->service->getMeta('xdebug');

        $this->service->removeMeta($iniMeta);
        $this->service->removeMeta($xdebugMeta);

        $env = [];
        $ini = [];
        foreach ($this->form->ini as $row) {
            $env[$row['name']] = $row['value'];
            $ini []= $row['name'];
        }

        if ($this->form->xdebug) {
            $env['PHP_INI_SCAN_DIR'] = ':/etc/php/xdebug';
            $ini []= 'PHP_INI_SCAN_DIR';
        }

        $iniMeta = new Entity\ServiceMeta();
        $iniMeta->setName('ini')
            ->setData($ini)
            ->setService($this->service);

        $xdebugMeta = new Entity\ServiceMeta();
        $xdebugMeta->setName('xdebug')
            ->setData(['install' => $this->form->xdebug])
            ->setService($this->service);

        $args = [];
        if ($this->form->blackfire['install'] ?? false) {
            $args ['BLACKFIRE_SERVER']= "blackfire-{$this->service->getSlug()}";
        }

        $build = $this->service->getBuild();
        $build->setArgs($args);

        $this->service->setBuild($build)
            ->setEnvironments($env);
    }

    public function getCreateParams() : array
    {
        return [
            'ini'           => $this->getIniSettings(),
            'xdebug'        => false,
            'blackfire'     => [
                'install'      => false,
                'server_id'    => '',
                'server_token' => '',
            ],
            'fileHighlight' => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        $iniMeta    = $this->service->getMeta('ini');
        $xdebugMeta = $this->service->getMeta('xdebug');

        $ini = [];
        foreach ($this->service->getEnvironments() as $k => $v) {
            if ($k === 'PHP_INI_SCAN_DIR') {
                continue;
            }

            if (in_array($k, $iniMeta->getData())) {
                $ini []= [
                    'env'   => $k,
                    'value' => $v,
                ];
            }
        }

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
            'ini'           => $ini,
            'xdebug'        => $xdebugMeta->getData()['install'],
            'blackfire'     => $blackfire,
            'fileHighlight' => 'ini',
        ];
    }

    public function getInternalVolumes() : array
    {
        $version = $this->getService()->getVersion();

        return [
            'files' => [
                'php-ini',
                'php-ini-cli',
                'fpm-conf',
                "dockerfile-{$version}",
            ],
            'other' => [
                'root',
            ],
        ];
    }

    protected function getIniSettings() : array
    {
        return [
            [
                'ini'   => 'display_errors',
                'env'   => 'PHP_DISPLAY_ERRORS',
                'value' => 'On',
            ],
            [
                'ini'   => 'error_reporting',
                'env'   => 'PHP_ERROR_REPORTING',
                'value' => '-1',
            ],
            [
                'ini'   => 'date.timezone',
                'env'   => 'DATE_TIMEZONE',
                'value' => 'UTC',
            ],
            [
                'ini'   => 'xdebug.remote_host',
                'env'   => 'XDEBUG_REMOTE_HOST',
                'value' => 'host.docker.internal',
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
