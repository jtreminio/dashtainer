<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;

class ServiceHandlerStore
{
    /** @var ServiceHandler\CrudInterface[] */
    protected $handlers = [];

    public function __construct(
        ServiceHandler\Apache $apache,
        ServiceHandler\Nginx $nginx,
        ServiceHandler\PhpFpm $phpfpm,
        ServiceHandler\MariaDB $mariaDB
    ) {
        $this->handlers []= $apache;
        $this->handlers []= $nginx;
        $this->handlers []= $phpfpm;
        $this->handlers []= $mariaDB;
    }

    public function getHandlerFromForm(
        Form\Service\CreateAbstract $form
    ) : ServiceHandler\CrudInterface {
        foreach ($this->handlers as $handler) {
            if (is_a($form, get_class($handler->getCreateForm()))) {
                return $handler;
            }
        }

        return null;
    }

    public function getHandlerFromType(
        Entity\DockerServiceType $type
    ) : ServiceHandler\CrudInterface {
        foreach ($this->handlers as $handler) {
            if ($type->getSlug() == $handler->getServiceTypeSlug()) {
                return $handler;
            }
        }

        return null;
    }
}
