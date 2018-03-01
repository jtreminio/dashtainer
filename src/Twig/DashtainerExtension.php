<?php

namespace Dashtainer\Twig;

use Behat\Transliterator\Transliterator;
use Zend\Stdlib\ArrayUtils;

class DashtainerExtension extends \Twig_Extension
{
    public function getFilters(): array
    {
        return [
            new \Twig_Filter('preg_quote', [$this, 'preg_quote']),
            new \Twig_Filter('str_replace', [$this, 'str_replace']),
            new \Twig_Filter('urlize', [$this, 'urlize']),
        ];
    }

    public function getFunctions(): array
    {
        return [
        ];
    }

    public function getTests() : array
    {
        return [
            new \Twig_Test('hash', [$this, 'isHashTable']),
            new \Twig_Test('string', [$this, 'is_string']),
        ];
    }

    ## Filters
    public function preg_quote($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = preg_quote($v);
            }

            return $value;
        }

        return preg_quote($value);
    }

    public function str_replace($subject, $search, $replace)
    {
        return str_replace($search, $replace, $subject);
    }

    public function urlize($string)
    {
        return Transliterator::urlize($string);
    }

    ## Functions

    ## Tests
    public function isHashTable($value, $allowEmpty = false) : bool
    {
        return ArrayUtils::isHashTable($value, $allowEmpty);
    }

    public function is_string($value) : bool
    {
        return is_string($value);
    }
}
