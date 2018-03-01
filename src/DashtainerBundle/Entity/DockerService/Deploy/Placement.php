<?php

namespace DashtainerBundle\Entity\DockerService\Deploy;

use DashtainerBundle\Util;

class Placement implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    protected $constraints = [];

    protected $preferences = [];

    public function getConstraints() : array
    {
        return $this->constraints;
    }

    /**
     * @param array $constraints
     * @return $this
     */
    public function setConstraints(array $constraints)
    {
        $this->constraints = $constraints;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addPreference(string $key, string $value = null)
    {
        $this->preferences[$key] = $value;

        return $this;
    }

    public function getPreferences() : array
    {
        return $this->preferences;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setPreferences(array $arr)
    {
        $this->preferences = $arr;

        return $this;
    }

    public function removePreference(string $key)
    {
        unset($this->preferences[$key]);
    }
}
