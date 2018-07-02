<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class Blackfire extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'blackfire';

    /** @var Form\Service\BlackfireCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\BlackfireCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setImage('blackfire/blackfire')
            ->setEnvironments([
                'BLACKFIRE_SERVER_ID'    => $this->form->server_id,
                'BLACKFIRE_SERVER_TOKEN' => $this->form->server_token,
            ]);
    }

    public function update()
    {
        $this->service->setEnvironments([
            'BLACKFIRE_SERVER_ID'    => $this->form->server_id,
            'BLACKFIRE_SERVER_TOKEN' => $this->form->server_token,
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
}
