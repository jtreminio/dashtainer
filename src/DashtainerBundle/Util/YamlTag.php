<?php

namespace DashtainerBundle\Util;

abstract class YamlTag
{
    protected const DBL_QUOTES = '__SPECIAL_DBL_QUOTES__';
    protected const NIL        = '__SPECIAL_NIL__';

    public static function parse(string $string) : string
    {
        $string = static::doubleQuotesParse($string);
        $string = static::nilValueParse($string);

        return $string;
    }

    public static function doubleQuotes(string $string) : string
    {
        return static::DBL_QUOTES . $string . static::DBL_QUOTES;
    }

    protected static function doubleQuotesParse(string $string) : string
    {
        $string  = str_replace("'" . static::DBL_QUOTES, '"', $string);
        return str_replace(static::DBL_QUOTES . "'", '"', $string);
    }

    public static function nilValue() : string
    {
        return static::NIL;
    }

    protected static function nilValueParse(string $string) : string
    {
        return str_replace(static::NIL, '', $string);
    }
}
