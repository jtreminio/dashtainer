<?php

namespace Dashtainer\Migrations;

use Dashtainer\Domain\Docker\ServiceWorker as Worker;
use Dashtainer\Form\Docker\Service as Form;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Repository\Docker as Repository;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Yaml\Yaml;

class Version1_0_6 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $serviceRepo     = new Repository\Service($em);
        $networkRepo     = new Repository\Network($em);
        $secretRepo      = new Repository\Secret($em);
        $serviceTypeRepo = new Repository\ServiceType($em);

        $this->migrateMariaDB($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
        $this->migrateMySQL($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
        $this->migratePostgreSQL($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }

    protected function migrateMariaDB(
        Repository\Service $serviceRepo,
        Repository\Network $networkRepo,
        Repository\Secret $secretRepo,
        Repository\ServiceType $serviceTypeRepo
    ) {
        $mariaDbType   = $serviceTypeRepo->findBySlug('mariadb');
        $mariaDbWorker = new Worker\MariaDB($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
        $refMethod = new \ReflectionMethod(Worker\MariaDB::class, 'createSecrets');
        $refMethod->setAccessible(true);

        foreach ($mariaDbType->getServices() as $service) {
            $env = $service->getEnvironments();

            $form = new Form\MariaDBCreate();
            $form->mysql_root_password = $env['MYSQL_ROOT_PASSWORD'];
            $form->mysql_database      = $env['MYSQL_DATABASE'];
            $form->mysql_user          = $env['MYSQL_USER'];
            $form->mysql_password      = $env['MYSQL_PASSWORD'];

            unset(
                $env['MYSQL_ROOT_PASSWORD'],
                $env['MYSQL_DATABASE'],
                $env['MYSQL_USER'],
                $env['MYSQL_PASSWORD']
            );

            $service->setEnvironments($env);

            $refMethod->invoke($mariaDbWorker, $service, $form);
        }
    }

    protected function migrateMySQL(
        Repository\Service $serviceRepo,
        Repository\Network $networkRepo,
        Repository\Secret $secretRepo,
        Repository\ServiceType $serviceTypeRepo
    ) {
        $mySQLType   = $serviceTypeRepo->findBySlug('mysql');
        $mySQLWorker = new Worker\MySQL($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
        $refMethod   = new \ReflectionMethod(Worker\MySQL::class, 'createSecrets');
        $refMethod->setAccessible(true);

        foreach ($mySQLType->getServices() as $service) {
            $env = $service->getEnvironments();

            $form = new Form\MySQLCreate();
            $form->mysql_root_password = $env['MYSQL_ROOT_PASSWORD'];
            $form->mysql_database      = $env['MYSQL_DATABASE'];
            $form->mysql_user          = $env['MYSQL_USER'];
            $form->mysql_password      = $env['MYSQL_PASSWORD'];

            unset(
                $env['MYSQL_ROOT_PASSWORD'],
                $env['MYSQL_DATABASE'],
                $env['MYSQL_USER'],
                $env['MYSQL_PASSWORD']
            );

            $service->setEnvironments($env);

            $refMethod->invoke($mySQLWorker, $service, $form);
        }
    }

    protected function migratePostgreSQL(
        Repository\Service $serviceRepo,
        Repository\Network $networkRepo,
        Repository\Secret $secretRepo,
        Repository\ServiceType $serviceTypeRepo
    ) {
        $psqlType   = $serviceTypeRepo->findBySlug('postgresql');
        $psqlWorker = new Worker\PostgreSQL($serviceRepo, $networkRepo, $secretRepo, $serviceTypeRepo);
        $refMethod  = new \ReflectionMethod(Worker\PostgreSQL::class, 'createSecrets');
        $refMethod->setAccessible(true);

        foreach ($psqlType->getServices() as $service) {
            $env = $service->getEnvironments();

            $form = new Form\PostgreSQLCreate();
            $form->postgres_db       = $env['POSTGRES_DB'];
            $form->postgres_user     = $env['POSTGRES_USER'];
            $form->postgres_password = $env['POSTGRES_PASSWORD'];

            unset(
                $env['POSTGRES_DB'],
                $env['POSTGRES_USER'],
                $env['POSTGRES_PASSWORD']
            );

            $service->setEnvironments($env);

            $refMethod->invoke($psqlWorker, $service, $form);
        }
    }
}
