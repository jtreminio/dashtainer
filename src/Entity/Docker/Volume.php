<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_volume")
 * @ORM\Entity()
 */
class Volume implements
    Util\HydratorInterface,
    Entity\EntityBaseInterface,
    Entity\SlugInterface
{
    use Util\HydratorTrait;
    use Entity\RandomIdTrait;
    use Entity\EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    protected $name;

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
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Project", inversedBy="volumes")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service", fetch="EAGER")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServiceVolume", mappedBy="project_volume")
     */
    protected $service_volumes;

    public function __construct()
    {
        $this->service_volumes = new Collections\ArrayCollection();
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

    public function getProject() : ?Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function setProject(Project $project = null)
    {
        if ($this->project === $project) {
            return $this;
        }

        $this->project = $project;

        if ($project) {
            $project->addVolume($this);
        }

        return $this;
    }

    public function getOwner() : ?Service
    {
        return $this->owner;
    }

    /**
     * @param Service $service
     * @return $this
     */
    public function setOwner(Service $service = null)
    {
        $this->owner = $service;

        return $this;
    }

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }

    /**
     * @param ServiceVolume $serviceVolume
     * @return $this
     */
    public function addServiceVolume(ServiceVolume $serviceVolume)
    {
        if ($this->service_volumes->contains($serviceVolume)) {
            return $this;
        }

        $this->service_volumes->add($serviceVolume);
        $serviceVolume->setProjectVolume($this);

        return $this;
    }

    public function removeServiceVolume(ServiceVolume $serviceVolume)
    {
        if (!$this->service_volumes->contains($serviceVolume)) {
            return;
        }

        $this->service_volumes->removeElement($serviceVolume);

        if ($serviceVolume->getProjectVolume() === $this) {
            $serviceVolume->setProjectVolume(null);
        }
    }

    /**
     * @return ServiceVolume[]|Collections\ArrayCollection
     */
    public function getServiceVolumes()
    {
        return $this->service_volumes;
    }
}
