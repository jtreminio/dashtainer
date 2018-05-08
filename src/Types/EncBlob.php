<?php

namespace Dashtainer\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class EncBlob extends Enc
{
    const NAME = 'enc_blob';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }
}
