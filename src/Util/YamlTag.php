<?php

namespace Dashtainer\Util;

abstract class YamlTag
{
    protected const DBL_QUOTES = '__SPECIAL_DBL_QUOTES__';
    protected const EMPTY_HASH = '__SPECIAL_EMPTY_HASH';
    protected const NIL        = '__SPECIAL_NIL__';

    public static function parse(string $string) : string
    {
        $string = static::doubleQuotesParse($string);
        $string = static::nilValueParse($string);
        $string = static::emptyHashParse($string);

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

    public static function emptyHash() : string
    {
        return static::EMPTY_HASH;
    }

    protected static function emptyHashParse(string $string) : string
    {
        return str_replace(static::EMPTY_HASH, '{  }', $string);
    }
}
