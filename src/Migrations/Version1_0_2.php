<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_2 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $serializer = $this->container->get('serializer');
        $kernel     = $this->container->get('kernel');

        $dataLoader = new DataLoader($serializer, $kernel->getEnvironment());
        $dataLoader->setBaseDir(__DIR__ . '/v1_0_2/data');

        $data = $dataLoader->getData('service_type_metas');

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            JOIN docker_service_type dst ON dstm.service_type_id = dst.id
            SET dstm.data = :data
            WHERE dstm.name = :name
              AND dst.name = "Nginx"
            LIMIT 1
        ', [
            ':data' => json_encode([$data['nginx_dockerfile']]),
            ':name' => 'Dockerfile',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            JOIN docker_service_type dst ON dstm.service_type_id = dst.id
            SET dstm.data = :data
            WHERE dstm.name = :name
              AND dst.name = "Apache"
            LIMIT 1
        ', [
            ':data' => json_encode([$data['apache_dockerfile']]),
            ':name' => 'Dockerfile',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode($data['ini-5.6']),
            ':name' => 'ini-5.6',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode($data['ini-7.0']),
            ':name' => 'ini-7.0',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode($data['ini-7.1']),
            ':name' => 'ini-7.1',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode($data['ini-7.2']),
            ':name' => 'ini-7.2',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode([$data['php5.6_dockerfile']]),
            ':name' => 'Dockerfile-5.6',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode([$data['php7.0_dockerfile']]),
            ':name' => 'Dockerfile-7.0',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode([$data['php7.1_dockerfile']]),
            ':name' => 'Dockerfile-7.1',
        ]);

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode([$data['php7.2_dockerfile']]),
            ':name' => 'Dockerfile-7.2',
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
            ':data' => json_encode([$data['php-fpm-startup']]),
            ':name' => 'php-fpm-startup',
        ]);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
