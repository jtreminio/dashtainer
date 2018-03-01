<?php

namespace DashtainerBundle\Entity\DockerService;

use DashtainerBundle\Util;

class Build implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @see https://docs.docker.com/compose/compose-file/#args
     */
    protected $args = [];

    /**
     * @see https://docs.docker.com/compose/compose-file/#cache_from
     */
    protected $cache_from = [];

    /**
     * @see https://docs.docker.com/compose/compose-file/#context
     */
    protected $context;

    /**
     * @see https://docs.docker.com/compose/compose-file/#dockerfile
     */
    protected $dockerfile;

    /**
     * @see https://docs.docker.com/compose/compose-file/#labels
     */
    protected $labels = [];

    /**
     * @see https://docs.docker.com/compose/compose-file/#shm_size
     */
    protected $shm_size;

    /**
     * @see https://docs.docker.com/compose/compose-file/#target
     */
    protected $target;

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

    public function getCacheFrom() : array
    {
        return $this->cache_from;
    }

    /**
     * @param array $cache_from
     * @return $this
     */
    public function setCacheFrom(array $cache_from)
    {
        $this->cache_from = $cache_from;

        return $this;
    }

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
    public function addLabel(string $key, string $value = null)
    {
        $this->labels[$key] = $value;

        return $this;
    }

    public function getLabels() : array
    {
        return $this->labels;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setLabels(array $arr)
    {
        $this->labels = $arr;

        return $this;
    }

    public function removeLabel(string $key)
    {
        unset($this->labels[$key]);
    }

    public function getShmSize() : ?string
    {
        return $this->shm_size;
    }

    /**
     * @param string $shm_size
     * @return $this
     */
    public function setShmSize(string $shm_size = null)
    {
        $this->shm_size = $shm_size;

        return $this;
    }

    public function getTarget() : ?string
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget(string $target = null)
    {
        $this->target = $target;

        return $this;
    }
}
