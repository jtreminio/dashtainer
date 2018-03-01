<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_network")
 * @ORM\Entity()
 */
class DockerNetwork implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    public const DRIVER_BRIDGE  = 'bridge';
    public const DRIVER_OVERLAY = 'overlay';

    protected const ALLOWED_DRIVERS = [
        self::DRIVER_BRIDGE,
        self::DRIVER_OVERLAY,
    ];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerProject", inversedBy="networks")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\DockerService", mappedBy="networks")
     */
    protected $services;

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    protected $name;

    /**
     * One of bridge, overlay
     *
     * @ORM\Column(name="driver", type="string", length=7, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#driver-1
     */
    protected $driver;

    /**
     * If false|null, not used in docker file
     *
     * If true:
     * networks:
     *   outside:
     *     external: true
     *
     * If string:
     * networks:
     *   outside:
     *     external:
     *       name: actual-name-of-network
     *
     * @ORM\Column(name="external", type="string", length=64, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#external-1
     */
    protected $external;

    /**
     * @ORM\Column(name="labels", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#labels-4
     */
    protected $labels = [];

    public function __construct()
    {
        $this->services = new Collections\ArrayCollection();
    }

    public function getProject() : ?DockerProject
    {
        return $this->project;
    }

    /**
     * @param DockerProject $project
     * @return $this
     */
    public function setProject(DockerProject $project)
    {
        $this->project = $project;

        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getDriver() : ?string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     * @return $this
     */
    public function setDriver(string $driver = null)
    {
        if (!in_array($driver, static::ALLOWED_DRIVERS)) {
            throw new \UnexpectedValueException();
        }

        $this->driver = $driver;

        return $this;
    }

    /**
     * @return bool|string
     */
    public function getExternal()
    {
        if (empty($this->external)) {
            return null;
        }

        if ($this->external === true || $this->external === 'true') {
            return true;
        }

        return $this->external;
    }

    /**
     * @param bool|string $external
     * @return $this
     */
    public function setExternal($external = null)
    {
        $this->external = empty($external)
            ? null
            : $external;

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

    /**
     * @param DockerService $service
     * @return $this
     */
    public function addService(DockerService $service)
    {
        $this->services[] = $service;

        return $this;
    }

    public function removeService(DockerService $service)
    {
        $this->services->removeElement($service);
    }

    /**
     * @return DockerService[]|Collections\ArrayCollection
     */
    public function getServices()
    {
        return $this->services;
    }
}
