<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_0 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
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
            new v1_0_0\DataFixtures($dataLoader),
        ]);
    }
}
