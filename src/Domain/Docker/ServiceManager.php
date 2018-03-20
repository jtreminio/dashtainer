<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;

class ServiceManager
{
    /** @var ServiceWorker\WorkerInterface[] */
    protected $workers = [];

    public function __construct(
        ServiceWorker\Apache $apache,
        ServiceWorker\Nginx $nginx,
        ServiceWorker\PhpFpm $phpfpm,
        ServiceWorker\MariaDB $mariaDB,
        ServiceWorker\MySQL $mySQL,
        ServiceWorker\PostgreSQL $postgreSQL,
        ServiceWorker\MongoDB $mongoDB,
        ServiceWorker\Redis $redis,
        ServiceWorker\Elasticsearch $elasticsearch,
        ServiceWorker\MailHog $mailHog,
        ServiceWorker\Beanstalkd $beanstalkd
    ) {
        $this->workers []= $apache;
        $this->workers []= $nginx;
        $this->workers []= $phpfpm;
        $this->workers []= $mariaDB;
        $this->workers []= $mySQL;
        $this->workers []= $postgreSQL;
        $this->workers []= $mongoDB;
        $this->workers []= $redis;
        $this->workers []= $elasticsearch;
        $this->workers []= $mailHog;
        $this->workers []= $beanstalkd;
    }

    public function getWorkerFromForm(
        Form\Docker\Service\CreateAbstract $form
    ) : ServiceWorker\WorkerInterface {
        foreach ($this->workers as $worker) {
            if (is_a($form, get_class($worker->getCreateForm()))) {
                return $worker;
            }
        }

        return null;
    }

    public function getWorkerFromType(
        Entity\Docker\ServiceType $type
    ) : ServiceWorker\WorkerInterface {
        foreach ($this->workers as $worker) {
            if ($type->getSlug() == $worker->getServiceTypeSlug()) {
                return $worker;
            }
        }

        return null;
    }
}
