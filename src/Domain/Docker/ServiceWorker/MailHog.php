<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class MailHog extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'mailhog';

    /** @var Form\Service\MailHogCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\MailHogCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setImage('mailhog/mailhog:latest')
            ->addLabel('traefik.backend', '{$COMPOSE_PROJECT_NAME}_' . $this->service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', "Host:{$this->service->getName()}.localhost")
            ->addLabel('traefik.port', 8025);
    }

    public function update()
    {
    }

    public function getCreateParams() : array
    {
        return [
            'fileHighlight' => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        return [
            'fileHighlight' => 'ini',
        ];
    }

    public function getInternalNetworks() : array
    {
        return [
            'public',
        ];
    }
}
