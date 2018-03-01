<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="volume")
 * @ORM\Entity()
 */
class Volume implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\Project", inversedBy="volumes")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(name="driver", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#driver
     */
    protected $driver;

    /**
     * @ORM\Column(name="driver_opts", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#driver_opts
     */
    protected $driver_opts = [];

    /**
     * @ORM\Column(name="external", type="boolean", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#external
     */
    protected $external;

    /**
     * @ORM\Column(name="labels", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#labels-3
     */
    protected $labels = [];

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

    public function getExternal() : ?bool
    {
        return $this->external;
    }

    /**
     * @param bool $external
     * @return $this
     */
    public function setExternal(bool $external = null)
    {
        $this->external = $external;

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

    public function getSlug(): string
    {
        return Transliterator::urlize($this->getName());
    }
}
