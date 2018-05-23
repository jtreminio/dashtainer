<?php

namespace Dashtainer\Migrations;

use Dashtainer\Domain\Docker as Domain;
use Dashtainer\Domain\Docker\ServiceWorker as Worker;
use Dashtainer\Form\Docker\Service as Form;
use Dashtainer\Repository\Docker as Repository;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Yaml\Yaml;

class Version1_0_6 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $serviceTypeRepo = new Repository\ServiceType($em);
        $secretDomain    = new Domain\Secret(new Repository\Secret($em));

        $this->migrateMariaDB($serviceTypeRepo, $secretDomain);
        $this->migrateMySQL($serviceTypeRepo, $secretDomain);
        $this->migratePostgreSQL($serviceTypeRepo, $secretDomain);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }

    protected function migrateMariaDB(
        Repository\ServiceType $serviceTypeRepo,
        Domain\Secret $secretDomain
    ) {
        $mariaDbType = $serviceTypeRepo->findBySlug('mariadb');

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

            $slug = $service->getSlug();
            $internalSecrets = [
                "{$slug}-mysql_host"          => $slug,
                "{$slug}-mysql_root_password" => $form->mysql_root_password,
                "{$slug}-mysql_database"      => $form->mysql_database,
                "{$slug}-mysql_user"          => $form->mysql_user,
                "{$slug}-mysql_password"      => $form->mysql_password,
            ];

            $secretDomain->createOwnedSecrets($service, $internalSecrets, true);
        }
    }

    protected function migrateMySQL(
        Repository\ServiceType $serviceTypeRepo,
        Domain\Secret $secretDomain
    ) {
        $mysqlType = $serviceTypeRepo->findBySlug('mysql');

        foreach ($mysqlType->getServices() as $service) {
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

            $slug = $service->getSlug();
            $internalSecrets = [
                "{$slug}-mysql_host"          => $slug,
                "{$slug}-mysql_root_password" => $form->mysql_root_password,
                "{$slug}-mysql_database"      => $form->mysql_database,
                "{$slug}-mysql_user"          => $form->mysql_user,
                "{$slug}-mysql_password"      => $form->mysql_password,
            ];

            $secretDomain->createOwnedSecrets($service, $internalSecrets, true);
        }
    }

    protected function migratePostgreSQL(
        Repository\ServiceType $serviceTypeRepo,
        Domain\Secret $secretDomain
    ) {
        $psqlType = $serviceTypeRepo->findBySlug('postgresql');

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

            $slug = $service->getSlug();
            $internalSecrets = [
                "{$slug}-postgres_host"     => $slug,
                "{$slug}-postgres_db"       => $form->postgres_db,
                "{$slug}-postgres_user"     => $form->postgres_user,
                "{$slug}-postgres_password" => $form->postgres_password,
            ];

            $secretDomain->createOwnedSecrets($service, $internalSecrets, true);
        }
    }
}
