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
        $dataLoader = $this->container->get('dashtainer.migrations.dataloader');

        $this->loadFixtures([
            new v1_0_0\DataFixtures($dataLoader),
        ]);
    }
}
