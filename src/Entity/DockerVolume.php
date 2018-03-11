<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_volume")
 * @ORM\Entity()
 */
class DockerVolume implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    public const CONSISTENCY_CACHED     = 'cached';
    public const CONSISTENCY_CONSISTENT = 'consistent';
    public const CONSISTENCY_DELEGATED  = 'delegated';

    protected const ALLOWED_CONSISTENCIES = [
        self::CONSISTENCY_CACHED,
        self::CONSISTENCY_CONSISTENT,
        self::CONSISTENCY_DELEGATED,
    ];

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    protected $name;

    /**
     * Only used in MacOS hosts.
     *
     * Not used directly in volumes main section, only within services.volumes
     *
     * @ORM\Column(name="consistency", type="string", length=10, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#caching-options-for-volume-mounts-docker-for-mac
     */
    protected $consistency;

    /**
     * @ORM\Column(name="driver", type="string", length=16, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#driver
     */
    protected $driver;

    /**
     * @ORM\Column(name="driver_opts", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#driver_opts
     */
    protected $driver_opts = [];

    /**
     * If false|null, not used in docker file
     *
     * If true:
     * volumes:
     *   data:
     *     external: true
     *
     * If string:
     * volumes:
     *   data:
     *     external:
     *       name: actual-name-of-volume
     *
     * @ORM\Column(name="external", type="string", length=64, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#external
     */
    protected $external;

    /**
     * @ORM\Column(name="labels", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#labels-3
     */
    protected $labels = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerProject", inversedBy="volumes")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\DockerService", mappedBy="project_volumes")
     */
    protected $services;

    public function __construct()
    {
        $this->services = new Collections\ArrayCollection();
    }

    public function getConsistency() : ?string
    {
        return $this->consistency;
    }

    /**
     * @param string $consistency
     * @return $this
     */
    public function setConsistency(string $consistency = null)
    {
        if (!in_array($consistency, static::ALLOWED_CONSISTENCIES)) {
            throw new \UnexpectedValueException();
        }

        $this->consistency = $consistency;

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
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addDriverOpt(string $key, string $value = null)
    {
        $this->driver_opts[$key] = $value;

        return $this;
    }

    public function getDriverOpts() : array
    {
        return $this->driver_opts;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setDriverOpts(array $arr)
    {
        $this->driver_opts = $arr;

        return $this;
    }

    public function removeDriverOpt(string $key)
    {
        unset($this->driver_opts[$key]);
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

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
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
