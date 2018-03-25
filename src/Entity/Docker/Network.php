<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_network")
 * @ORM\Entity()
 */
class Network implements Util\HydratorInterface, Entity\EntityBaseInterface
{
    use Util\HydratorTrait;
    use Entity\RandomIdTrait;
    use Entity\EntityBaseTrait;

    public const DRIVER_BRIDGE  = 'bridge';
    public const DRIVER_OVERLAY = 'overlay';

    protected const ALLOWED_DRIVERS = [
        self::DRIVER_BRIDGE,
        self::DRIVER_OVERLAY,
    ];

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
     * @ORM\Column(name="is_public", type="boolean")
     */
    protected $is_public = false;

    /**
     * @ORM\Column(name="is_editable", type="boolean")
     */
    protected $is_editable = true;

    /**
     * @ORM\Column(name="labels", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#labels-4
     */
    protected $labels = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Project", inversedBy="networks")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\Docker\Service", mappedBy="networks")
     */
    protected $services;

    public function __construct()
    {
        $this->services = new Collections\ArrayCollection();
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

    public function getIsPublic() : bool
    {
        return $this->is_public;
    }

    /**
     * @param bool $is_public
     * @return $this
     */
    public function setIsPublic(bool $is_public)
    {
        $this->is_public = $is_public;

        return $this;
    }

    public function getIsEditable() : bool
    {
        return $this->is_editable;
    }

    /**
     * @param bool $is_editable
     * @return $this
     */
    public function setIsEditable(bool $is_editable)
    {
        $this->is_editable = $is_editable;

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

    public function getProject() : ?Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @param Service $service
     * @return $this
     */
    public function addService(Service $service)
    {
        if ($this->services->contains($service)) {
            return $this;
        }

        $this->services->add($service);
        $service->addNetwork($this);

        return $this;
    }

    public function removeService(Service $service)
    {
        if (!$this->services->contains($service)) {
            return;
        }

        $this->services->removeElement($service);
        $service->removeNetwork($this);
    }

    /**
     * @return Service[]|Collections\ArrayCollection
     */
    public function getServices()
    {
        return $this->services;
    }
}
