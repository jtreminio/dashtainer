<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_5 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $this->addSql('
            CREATE TABLE docker_service_secret (
                id VARCHAR(8) NOT NULL,
                project_secret_id VARCHAR(8) DEFAULT NULL,
                service_id VARCHAR(8) NOT NULL,
                target VARCHAR(64) NOT NULL,
                uid VARCHAR(32) NOT NULL,
                gid VARCHAR(32) NOT NULL,
                mode VARCHAR(4) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_7CB10CD762B29D0 (project_secret_id),
                INDEX IDX_7CB10CDED5CA9E6 (service_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
        ');

        $this->addSql('
            ALTER TABLE docker_service_secret
                ADD CONSTRAINT FK_7CB10CD762B29D0
                FOREIGN KEY (project_secret_id) REFERENCES docker_secret (id);
        ');

        $this->addSql('
            ALTER TABLE docker_service_secret
                ADD CONSTRAINT FK_7CB10CDED5CA9E6
                FOREIGN KEY (service_id) REFERENCES docker_service (id);
        ');

        $this->addSql('
            ALTER TABLE docker_secret
                ADD file VARCHAR(255) DEFAULT NULL AFTER external,
                ADD owner_id VARCHAR(8) DEFAULT NULL AFTER project_id;
        ');

        $this->addSql('
            ALTER TABLE docker_secret
              ADD contents LONGTEXT DEFAULT NULL AFTER file;
        ');

        $this->addSql('
            ALTER TABLE docker_secret
                ADD CONSTRAINT FK_BBB588937E3C61F9
                FOREIGN KEY (owner_id) REFERENCES docker_service (id);
        ');

        $this->addSql('
            CREATE INDEX IDX_BBB588937E3C61F9
                ON docker_secret (owner_id);
        ');

        $this->addSql('
            DROP TABLE docker_services_secrets;
        ');
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
