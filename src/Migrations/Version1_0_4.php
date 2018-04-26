<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Yaml\Yaml;

class Version1_0_4 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $serializer = $this->container->get('serializer');
        $kernel     = $this->container->get('kernel');

        $dataLoader = new DataLoader($serializer, $kernel->getEnvironment());
        $dataLoader->setBaseDir(__DIR__ . '/v1_0_4/data');

        $data = $dataLoader->getData('service_type_metas');

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            JOIN docker_service_type dst ON dstm.service_type_id = dst.id
            SET dstm.data = :data
            WHERE dstm.name = :name
              AND dst.name = "Apache"
        ', [
            ':data' => json_encode($data['apache_modules']),
            ':name' => 'modules',
        ]);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
        $serializer = $this->container->get('serializer');
        $kernel     = $this->container->get('kernel');

        $dataLoader = new DataLoader($serializer, $kernel->getEnvironment());

        $this->loadFixtures([
            new v1_0_4\DataFixtures($dataLoader),
        ]);
    }
}
