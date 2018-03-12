<?php

namespace Dashtainer\Util;

abstract class Strings
{
    public static function removeExtraLineBreaks(string $string)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }

    public static function hostname(string $string) : string
    {
        return preg_replace("/[^a-zA-Z0-9\-]/", '-', $string);
    }

    public static function filename(string $string) : string
    {
        return preg_replace("/[^a-zA-Z0-9._\-]/", '', $string);
    }
}
