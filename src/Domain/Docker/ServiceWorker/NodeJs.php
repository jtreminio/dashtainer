<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Form\Docker as Form;

class NodeJs extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'node-js';

    /** @var Form\Service\NodeJsCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\NodeJsCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name)
            ->setImage("node:{$this->form->version}")
            ->setVersion($this->form->version)
            ->setExpose([$this->form->port])
            ->setCommand([$this->form->command])
            ->setWorkingDir('/var/www');

        $portMeta = new Entity\ServiceMeta();
        $portMeta->setName('port')
            ->setData([$this->form->port])
            ->setService($this->service);
    }

    public function update()
    {
        $this->service->setExpose([$this->form->port])
            ->setCommand([$this->form->command]);

        $portMeta = $this->service->getMeta('port');
        $portMeta->setData([$this->form->port]);
    }

    public function getCreateParams() : array
    {
        return [
            'fileHighlight' => 'ini',
        ];
    }

    public function getViewParams() : array
    {
        $portMeta = $this->service->getMeta('port');

        return [
            'port'          => $portMeta->getData()[0],
            'command'       => $this->service->getCommand(),
            'fileHighlight' => 'ini',
        ];
    }

    public function getInternalVolumes() : array
    {
        return [
            'files' => [],
            'other' => [
                'root',
            ],
        ];
    }
}
