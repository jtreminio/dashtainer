<?php

namespace Dashtainer\Entity\Docker\Service;

use Dashtainer\Util;

class Ulimits implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    protected $memlock = [];

    protected $nofile = [];

    protected $nproc;

    public function getMemlock() : array
    {
        return $this->memlock;
    }

    /**
     * @param int $soft
     * @param int $hard
     * @return $this
     */
    public function setMemlock(int $soft, int $hard)
    {
        $this->memlock = [
            'soft' => $soft,
            'hard' => $hard,
        ];

        return $this;
    }

    public function getNofile() : array
    {
        return $this->nofile;
    }

    /**
     * @param int $soft
     * @param int $hard
     * @return $this
     */
    public function setNofile(int $soft, int $hard)
    {
        $this->nofile = [
            'soft' => $soft,
            'hard' => $hard,
        ];

        return $this;
    }

    public function getNproc() : ?int
    {
        return $this->nproc;
    }

    /**
     * @param int $nproc
     * @return $this
     */
    public function setNproc(int $nproc)
    {
        $this->nproc = $nproc;

        return $this;
    }
}
