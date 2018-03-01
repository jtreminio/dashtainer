<?php

namespace DashtainerBundle\Entity\Service;

use DashtainerBundle\Util;

class Build implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @see https://docs.docker.com/compose/compose-file/#context
     */
    protected $context;

    /**
     * @see https://docs.docker.com/compose/compose-file/#dockerfile
     */
    protected $dockerfile;

    /**
     * @see https://docs.docker.com/compose/compose-file/#args
     */
    protected $args = [];

    public function getContext() : ?string
    {
        return $this->context;
    }

    /**
     * @param string $context
     * @return $this
     */
    public function setContext(string $context = null)
    {
        $this->context = $context;

        return $this;
    }

    public function getDockerfile() : ?string
    {
        return $this->dockerfile;
    }

    /**
     * @param string $dockerfile
     * @return $this
     */
    public function setDockerfile(string $dockerfile = null)
    {
        $this->dockerfile = $dockerfile;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addArg(string $key, string $value = null)
    {
        $this->args[$key] = $value;

        return $this;
    }

    public function getArgs() : array
    {
        return $this->args;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setArgs(array $arr)
    {
        $this->args = $arr;

        return $this;
    }

    public function removeArg(string $key)
    {
        unset($this->args[$key]);
    }
}
