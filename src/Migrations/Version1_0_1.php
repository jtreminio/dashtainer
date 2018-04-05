<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_1 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $data = <<<'EOD'
; used in both PHP-FPM and PHP-CLI
; make sure to change "host.docker.internal" to match your host if required

[xdebug]
xdebug.remote_host = "host.docker.internal"
xdebug.default_enable = 1
xdebug.remote_autostart = 1
xdebug.remote_connect_back = 0
xdebug.remote_enable = 1
xdebug.remote_handler = "dbgp"
xdebug.remote_port = 9000
EOD;

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            SET dstm.data = :data
            WHERE dstm.name = :name
            LIMIT 1
        ', [
            ':data' => json_encode([$data]),
            ':name' => 'ini-xdebug',
        ]);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
