<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;

class ServiceManager
{
    /** @var ServiceWorker\WorkerInterface[] */
    protected $workers = [];

    public function __construct(
        ServiceWorker\Adminer $adminer,
        ServiceWorker\Apache $apache,
        ServiceWorker\Beanstalkd $beanstalkd,
        ServiceWorker\Elasticsearch $elasticsearch,
        ServiceWorker\MailHog $mailHog,
        ServiceWorker\MariaDB $mariaDB,
        ServiceWorker\MongoDB $mongoDB,
        ServiceWorker\MySQL $mySQL,
        ServiceWorker\Nginx $nginx,
        ServiceWorker\PhpFpm $phpfpm,
        ServiceWorker\PostgreSQL $postgreSQL,
        ServiceWorker\Redis $redis
    ) {
        $this->workers []= $adminer;
        $this->workers []= $apache;
        $this->workers []= $beanstalkd;
        $this->workers []= $elasticsearch;
        $this->workers []= $mailHog;
        $this->workers []= $mariaDB;
        $this->workers []= $mongoDB;
        $this->workers []= $mySQL;
        $this->workers []= $nginx;
        $this->workers []= $phpfpm;
        $this->workers []= $postgreSQL;
        $this->workers []= $redis;
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
