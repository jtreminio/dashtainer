<?php

namespace DashtainerBundle\Entity\Service;

use DashtainerBundle\Util;

class Deploy implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    public const MODE_GLOBAL     = 'global';
    public const MODE_REPLICATED = 'replicated';

    protected const ALLOWED_MODES = [
        self::MODE_GLOBAL,
        self::MODE_REPLICATED,
    ];

    /**
     * @see https://docs.docker.com/compose/compose-file/#labels-1
     */
    protected $labels = [];

    /**
     * One of global, replicated
     *
     * @see https://docs.docker.com/compose/compose-file/#mode
     */
    protected $mode = 'replicated';

    /**
     * @uses \DashtainerBundle\Entity\Service\Deploy\Placement
     * @see https://docs.docker.com/compose/compose-file/#placement
     */
    protected $placement = [];

    /**
     * Only if $mode == 'replicated'
     *
     * @see https://docs.docker.com/compose/compose-file/#replicas
     */
    protected $replicas;

    /**
     * @uses \DashtainerBundle\Entity\Service\Deploy\Resources
     * @see https://docs.docker.com/compose/compose-file/#resources
     */
    protected $resources = [];

    /**
     * @uses \DashtainerBundle\Entity\Service\Deploy\RestartPolicy
     * @see https://docs.docker.com/compose/compose-file/#restart_policy
     */
    protected $restart_policy = [];

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

    public function getMode() : string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode(string $mode = null)
    {
        if (!in_array($mode, static::ALLOWED_MODES)) {
            throw new \UnexpectedValueException();
        }

        $this->mode = $mode;

        return $this;
    }

    public function getPlacement() : Deploy\Placement
    {
        $placement = new Deploy\Placement();
        $placement->fromArray($this->placement);

        return $placement;
    }

    /**
     * @param Deploy\Placement $placement
     * @return $this
     */
    public function setPlacement(Deploy\Placement $placement = null)
    {
        $this->placement = $placement
            ? $placement->toArray()
            : [];

        return $this;
    }

    public function getReplicas() : ?int
    {
        return $this->replicas;
    }

    /**
     * @param int $replicas
     * @return $this
     */
    public function setReplicas(int $replicas = null)
    {
        $this->replicas = $replicas;

        return $this;
    }

    public function getResources() : Deploy\Resources
    {
        $resources = new Deploy\Resources();
        $resources->fromArray($this->resources);

        return $resources;
    }

    /**
     * @param Deploy\Resources $resources
     * @return $this
     */
    public function setResources(Deploy\Resources $resources = null)
    {
        $this->resources = $resources
            ? $resources->toArray()
            : [];

        return $this;
    }

    public function getRestartPolicy() : Deploy\RestartPolicy
    {
        $policy = new Deploy\RestartPolicy();
        $policy->fromArray($this->restart_policy);

        return $policy;
    }

    /**
     * @param Deploy\RestartPolicy $policy
     * @return $this
     */
    public function setRestartPolicy(Deploy\RestartPolicy $policy = null)
    {
        $this->restart_policy = $policy
            ? $policy->toArray()
            : [];

        return $this;
    }
}
