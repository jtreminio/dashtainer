<?php

namespace Dashtainer\Types;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Enc extends Type
{
    const NAME = 'enc';

    /** @var Key */
    protected static $key;

    public static function setKey($key)
    {
        static::$key = Key::loadFromAsciiSafeString($key);
    }

    public static function getKey()
    {
        return static::$key;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $decrypted = Crypto::decrypt($value, static::$key);

            return $decrypted;
        } catch (WrongKeyOrModifiedCiphertextException $ex) {
            return null;
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return is_null($value) ? null : Crypto::encrypt($value, static::$key);
    }

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBinaryTypeDeclarationSQL($fieldDeclaration);
    }
}
