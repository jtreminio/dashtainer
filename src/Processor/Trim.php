<?php

namespace Dashtainer\Processor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class Trim implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        return trim($getEnv($name));
    }

    public static function getProvidedTypes()
    {
        return ['trim' => 'string'];
    }
}
