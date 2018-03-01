<?php

namespace DashtainerBundle\Entity\Service\Deploy;

use DashtainerBundle\Util;

class RestartPolicy implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    public const CONDITION_NONE       = 'none';
    public const CONDITION_ON_FAILURE = 'on-failure';
    public const CONDITION_ANY        = 'any';

    protected const ALLOWED_CONDITIONS = [
        self::CONDITION_NONE,
        self::CONDITION_ON_FAILURE,
        self::CONDITION_ANY,
    ];

    // One of none, on-failure, any
    protected $condition = 'any';

    protected $delay = '0';

    protected $max_attempts = null;

    protected $window = '0';

    public function getCondition() : string
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return $this
     */
    public function setCondition(string $condition)
    {
        if (!in_array($condition, static::ALLOWED_CONDITIONS)) {
            throw new \UnexpectedValueException();
        }

        $this->condition = $condition;

        return $this;
    }

    /**
     * @return string
     */
    public function getDelay(): string
    {
        return $this->delay;
    }

    /**
     * @param string $delay 0 or "1s", "5s", etc
     * @return $this
     */
    public function setDelay($delay)
    {
        $this->delay = empty($delay)
            ? '0'
            : $delay;

        return $this;
    }

    public function getMaxAttempts() : ?int
    {
        return $this->max_attempts;
    }

    /**
     * @param int $max_attempts
     * @return $this
     */
    public function setMaxAttempts(int $max_attempts = null)
    {
        $this->max_attempts = $max_attempts;

        return $this;
    }

    public function getWindow() : string
    {
        return $this->window;
    }

    /**
     * @param string $window 0 or "1s", "5s", etc
     * @return $this
     */
    public function setWindow($window)
    {
        $this->window = empty($window)
            ? '0'
            : $window;

        return $this;
    }
}
