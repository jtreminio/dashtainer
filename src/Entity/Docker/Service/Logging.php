<?php

namespace Dashtainer\Entity\Docker\Service;

use Dashtainer\Util;

class Logging implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    protected $driver = 'json-file';

    protected $options = [];

    public function getDriver() : string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     * @return $this
     */
    public function setDriver(string $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addOption(string $key, string $value = null)
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setOptions(array $arr)
    {
        $this->options = $arr;

        return $this;
    }

    public function removeOption(string $key)
    {
        unset($this->options[$key]);
    }
}
