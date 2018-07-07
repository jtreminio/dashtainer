<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class Redis
    extends WorkerAbstract
    implements WorkerParentInterface, WorkerServiceRepoInterface
{
    use WorkerServiceRepoTrait;

    public const SERVICE_TYPE_SLUG = 'redis';

    /** @var Form\Service\RedisCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\RedisCreate();
    }

    public function create()
    {
        $version = (string) number_format($this->form->version, 1);

        $this->service->setName($this->form->name)
            ->setImage("redis:{$version}")
            ->setVersion($version);

        $this->form->secrets['redis_host']['data'] = $this->service->getSlug();
    }

    public function update()
    {
    }

    public function getCreateParams() : array
    {
        return [
            'redis_commander' => false,
            'fileHighlight'   => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        $redisCommander = $this->getRedisCommanderChild();

        return [
            'redis_commander' => !empty($redisCommander),
            'fileHighlight'   => 'ini',
        ];
    }

    public function getInternalPorts() : array
    {
        return [
            [null, 6379, 'tcp']
        ];
    }

    public function getInternalSecrets() : array
    {
        return [
            'redis_host',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [
            ],
            'other' => [
                'datadir',
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

        $existingRedisCommanderChild = $this->getRedisCommanderChild();

        // existing, delete
        if (empty($this->form->redis_commander) && $existingRedisCommanderChild) {
            $data['delete'] []= $existingRedisCommanderChild;
        }

        // existing, update
        if (!empty($this->form->redis_commander) && $existingRedisCommanderChild) {
            $childForm = RedisCommander::getFormInstance();
            $childForm->fromArray([
                'hostname'   => $this->service->getSlug(),
                'redis_host' => "redis:{$this->service->getSlug()}:6379",
            ]);

            foreach ($this->service->getNetworks() as $network) {
                $childForm->networks [$network->getId()]= [
                    'id'   => $network->getId(),
                    'name' => $network->getName(),
                ];
            }

            $data['update'] []= [
                'service' => $existingRedisCommanderChild,
                'form'    => $childForm,
            ];
        }

        // not existing, create
        if (!empty($this->form->redis_commander) && !$existingRedisCommanderChild) {
            $childForm = RedisCommander::getFormInstance();
            $childForm->fromArray([
                'name'       => "{$this->service->getSlug()}-commander",
                'hostname'   => $this->service->getSlug(),
                'redis_host' => "redis:{$this->service->getSlug()}:6379",
            ]);

            foreach ($this->service->getNetworks() as $network) {
                $childForm->networks [$network->getId()]= [
                    'id'   => $network->getId(),
                    'name' => $network->getName(),
                ];
            }

            $data['create'] []= [
                'serviceTypeSlug' => RedisCommander::SERVICE_TYPE_SLUG,
                'form'            => $childForm,
            ];
        }

        return $data;
    }

    protected function getRedisCommanderChild() : ?Entity\Service
    {
        return $this->repo->findChildByTypeName(
            $this->service,
            RedisCommander::SERVICE_TYPE_SLUG
        );
    }
}
