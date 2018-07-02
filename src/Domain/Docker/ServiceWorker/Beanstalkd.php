<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Form\Docker as Form;

class Beanstalkd extends WorkerAbstract
{
    public const SERVICE_TYPE_SLUG = 'beanstalkd';

    /** @var Form\Service\BeanstalkdCreate */
    protected $form;

    public static function getFormInstance() : Form\Service\CreateAbstract
    {
        return new Form\Service\BeanstalkdCreate();
    }

    public function create()
    {
        $this->service->setName($this->form->name);

        $build = $this->service->getBuild();
        $build->setContext("./{$this->service->getSlug()}")
            ->setDockerfile('Dockerfile');

        $this->service->setBuild($build);
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

    public function getInternalVolumes() : array
    {
        return [
            'files' => [
                'dockerfile'
            ],
            'other' => [
                'datadir'
            ],
        ];
    }
}
