<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class RedisCommander extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'redis-commander';

    /** @var Form\Service\RedisCommanderCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\RedisCommanderCreate();
    }

    public function create()
    {
        $project = $this->service->getProject();

        $this->service->setName($this->form->name)
            ->setImage('rediscommander/redis-commander:latest')
            ->setEnvironments([
                'REDIS_HOSTS' => $this->form->redis_host,
            ])
            ->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $this->service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.port', 8081);

        $frontendRule = sprintf('Host:%s.%s.localhost',
            $this->form->hostname,
            $project->getSlug()
        );

        $this->service->addLabel('traefik.frontend.rule', $frontendRule);

    }

    public function update()
    {
        $this->service->setEnvironments([
            'REDIS_HOSTS' => $this->form->redis_host,
        ]);
    }

    public function getCreateParams() : array
    {
        return [];
    }

    public function getViewParams() : array
    {
        return [];
    }

    public function getInternalNetworks() : array
    {
        return [
            'public',
        ];
    }
}
