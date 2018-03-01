<?php

namespace DashtainerBundle\Entity\DockerService\Deploy;

use DashtainerBundle\Util;

class Resources implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    protected $limits = [];

    protected $reservations = [];

    public function getLimits() : array
    {
        return $this->limits;
    }

    /**
     * @param string|null $cpus
     * @param string|null $memory
     * @return $this
     */
    public function setLimits(string $cpus = null, string $memory = null)
    {
        $this->limits = [
            'cpus'   => $cpus,
            'memory' => $memory,
        ];

        return $this;
    }

    public function getReservations() : array
    {
        return $this->reservations;
    }

    /**
     * @param string|null $cpus
     * @param string|null $memory
     * @return $this
     */
    public function setReservations(string $cpus = null, string $memory = null)
    {
        $this->reservations = [
            'cpus'   => $cpus,
            'memory' => $memory,
        ];

        return $this;
    }
}
