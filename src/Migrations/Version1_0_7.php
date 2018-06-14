<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_7 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $this->addSql('
            ALTER TABLE docker_service_secret
                CHANGE is_internal is_internal TINYINT(1) NOT NULL;
        ');

        $this->addSql('
            ALTER TABLE docker_volume
                ADD owner_id VARCHAR(8) DEFAULT NULL AFTER project_id;

            UPDATE docker_volume dv
            SET dv.owner_id = (
                SELECT ds.id
                FROM docker_service ds
                JOIN docker_service_volume dsv on ds.id = dsv.service_id
                WHERE dsv.project_volume_id = dv.id
            )
        ');

        $this->addSql('
            ALTER TABLE docker_volume
                ADD CONSTRAINT FK_5E8DADA87E3C61F9
                FOREIGN KEY (owner_id) REFERENCES docker_service (id);
        ');

        $this->addSql('
            CREATE INDEX IDX_5E8DADA87E3C61F9 ON docker_volume (owner_id);
        ');

        $this->addSql('
            ALTER TABLE docker_service_volume
                CHANGE filetype filetype VARCHAR(32) NOT NULL,
                ADD prepend TINYINT(1) NOT NULL DEFAULT 0 AFTER target,
                ADD is_internal TINYINT(1) NOT NULL AFTER owner;
        ');

        $this->addSql("
            UPDATE docker_service_volume
            SET is_internal = 1
            WHERE owner = 'system';
        ");

        $this->addSql("
            ALTER TABLE docker_service_volume
            DROP owner;
        ");

        $this->addSql("
            ALTER TABLE docker_service_volume
                CHANGE prepend prepend TINYINT(1) NOT NULL;
            ALTER TABLE docker_secret
                CHANGE contents data LONGBLOB DEFAULT NULL COMMENT '(DC2Type:enc_blob)';
        ");

    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
