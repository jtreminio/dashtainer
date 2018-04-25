<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_3 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $serializer = $this->container->get('serializer');
        $kernel     = $this->container->get('kernel');

        $dataLoader = new DataLoader($serializer, $kernel->getEnvironment());
        $dataLoader->setBaseDir(__DIR__ . '/v1_0_3/data');

        $data = $dataLoader->getData('service_type_metas');

        $this->addSql('
            INSERT INTO docker_service_type_meta (
                service_type_id, name, data, created_at, updated_at
            )
            VALUES (
                (SELECT dst.id FROM docker_service_type dst WHERE dst.name = "PHP-FPM" LIMIT 1),
                :name,
                :data,
                NOW(),
                NOW()
            )
        ', [
            ':data' => json_encode([$data['xdebug_bin']]),
            ':name' => 'xdebug-bin',
        ]);

        $this->addSql('
            INSERT INTO docker_service_type_meta (
                service_type_id, name, data, created_at, updated_at
            )
            VALUES (
                (SELECT dst.id FROM docker_service_type dst WHERE dst.name = "PHP-FPM" LIMIT 1),
                :name,
                :data,
                NOW(),
                NOW()
            )
        ', [
            ':data' => json_encode([$data['xdebug_cli_ini']]),
            ':name' => 'ini-xdebug-cli',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
        ', [
            ':data' => json_encode([$data['xdebug_ini']]),
            ':name' => 'ini-xdebug',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
        ', [
            ':data' => json_encode([$data['php5.6_dockerfile']]),
            ':name' => 'Dockerfile-5.6',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
        ', [
            ':data' => json_encode([$data['php7.0_dockerfile']]),
            ':name' => 'Dockerfile-7.0',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
        ', [
            ':data' => json_encode([$data['php7.1_dockerfile']]),
            ':name' => 'Dockerfile-7.1',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
        ', [
            ':data' => json_encode([$data['php7.2_dockerfile']]),
            ':name' => 'Dockerfile-7.2',
        ]);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
