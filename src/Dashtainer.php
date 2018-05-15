<?php

namespace Dashtainer;

use Doctrine\DBAL;
use Doctrine\ORM;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Dashtainer extends Bundle
{
    public function boot()
    {
        $container = $this->container;
        $manager   = $container->get('doctrine')
            ->getManager();

        if (!Types\Enc::getKey()) {
            Types\Enc::setKey($container->getParameter('enc_key'));
        }

        if (!DBAL\Types\Type::hasType('enc')) {
            DBAL\Types\Type::addType('enc', Types\Enc::class);

            /** @var DBAL\Connection $conn */
            $conn = $manager->getConnection();
            $conn->getDatabasePlatform()->registerDoctrineTypeMapping('db_enc', 'enc');
        }

        if (!DBAL\Types\Type::hasType('enc_blob')) {
            DBAL\Types\Type::addType('enc_blob', Types\EncBlob::class);

            /** @var DBAL\Connection $conn */
            $conn = $manager->getConnection();
            $conn->getDatabasePlatform()->registerDoctrineTypeMapping('db_enc_blob', 'enc_blob');
        }

        parent::boot();
    }
}
