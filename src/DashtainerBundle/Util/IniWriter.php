<?php

namespace DashtainerBundle\Util;

use WriteiniFile\WriteiniFile;

class IniWriter
{
    public static function writeData(
        string $filename,
        array $value
    ) : bool {
        $iniWriter = new WriteiniFile($filename);
        $iniWriter->add($value);

        return $iniWriter->write();
    }
}
