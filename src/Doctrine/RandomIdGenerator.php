<?php

namespace Dashtainer\Doctrine;

use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\EntityManager;
use RandomLib;

class RandomIdGenerator extends AbstractIdGenerator
{
    const ALLOWED_CHAR = '1234567890abcdefghijklmnopqrstuvwxyz';

    public function generate(EntityManager $em, $entity) : string
    {
        $entityName = $em->getClassMetadata(get_class($entity))->getName();

        $factory   = new RandomLib\Factory;
        $generator = $factory->getLowStrengthGenerator();

        while (true) {
            $randomString = $generator->generateString(8, static::ALLOWED_CHAR);

            $item = $em->find($entityName, $randomString);

            if (!$item) {
                return $randomString;
            }
        }
    }
}
