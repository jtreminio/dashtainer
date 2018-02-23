<?php

namespace PodYardBundle\Util;

abstract class Strings
{
    public static function removeExtraLineBreaks(string $string)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }
}
