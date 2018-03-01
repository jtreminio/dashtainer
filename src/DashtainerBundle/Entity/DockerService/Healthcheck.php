<?php

namespace DashtainerBundle\Entity\DockerService;

use DashtainerBundle\Util;

class Healthcheck implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    public const TEST_TYPE_CMD       = 'CMD';
    public const TEST_TYPE_CMD_SHELL = 'CMD-SHELL';
    public const TEST_TYPE_NONE      = 'none';

    protected const ALLOWED_TEST_TYPES = [
        self::TEST_TYPE_CMD,
        self::TEST_TYPE_CMD_SHELL,
        self::TEST_TYPE_NONE,
    ];

    protected $test = [];

    protected $interval = '1m30s';

    protected $timeout = '10s';

    protected $retries = '3';

    public function getTest(): array
    {
        return $this->test;
    }

    /**
     * @param string $type
     * @param array  $test
     * @return $this
     */
    public function setTest(string $type, array $test)
    {
        if (!in_array($type, static::ALLOWED_TEST_TYPES)) {
            throw new \UnexpectedValueException();
        }

        $test = array_merge([$type], $test);

        $this->test = array_values($test);

        return $this;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param string $interval
     * @return $this
     */
    public function setInterval($interval)
    {
        $this->interval = empty($interval)
            ? '0'
            : $interval;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param string $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = empty($timeout)
            ? '0'
            : $timeout;

        return $this;
    }

    /**
     * @return string
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param string $retries
     * @return $this
     */
    public function setRetries($retries)
    {
        $this->retries = empty($retries)
            ? '0'
            : $retries;

        return $this;
    }
}
