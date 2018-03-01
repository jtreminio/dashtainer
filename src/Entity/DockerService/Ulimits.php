<?php

namespace Dashtainer\Entity\DockerService;

use Dashtainer\Util;

class Ulimits implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    protected $nproc;

    protected $nofile = [];

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

    public function getNofile(): array
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
}
