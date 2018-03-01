<?php

namespace DashtainerBundle\Util;

abstract class ArrayUtils
{
    public static function joinKeysValues(array $array, string $separator = '=') : array
    {
        $joined = [];

        foreach ($array as $key => $value) {
            $string = trim($value) == ''
                ? "{$key}"
                : "{$key}{$separator}{$value}";

            $joined[] = $string;
        }

        return $joined;
    }
}
