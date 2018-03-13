<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;

class HandlerStore
{
    /** @var Service\HandlerInterface[] */
    protected $handlers = [];

    public function __construct(
        Service\Apache $apache,
        Service\Nginx $nginx,
        Service\PhpFpm $phpfpm,
        Service\MariaDB $mariaDB
    ) {
        $this->handlers []= $apache;
        $this->handlers []= $nginx;
        $this->handlers []= $phpfpm;
        $this->handlers []= $mariaDB;
    }

    public function getHandlerFromForm(
        Form\Docker\Service\CreateAbstract $form
    ) : Service\HandlerInterface {
        foreach ($this->handlers as $handler) {
            if (is_a($form, get_class($handler->getCreateForm()))) {
                return $handler;
            }
        }

        return null;
    }

    public function getHandlerFromType(
        Entity\Docker\ServiceType $type
    ) : Service\HandlerInterface {
        foreach ($this->handlers as $handler) {
            if ($type->getSlug() == $handler->getServiceTypeSlug()) {
                return $handler;
            }
        }

        return null;
    }
}
