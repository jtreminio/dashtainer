<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_secret")
 * @ORM\Entity()
 */
class Secret implements
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
     * If false|null, not used in docker file
     *
     * If true:
     * secrets:
     *   my_first_secret:
     *     external: true
     *
     * If string:
     * secrets:
     *   my_first_secret:
     *     external:
     *       name: redis_secret
     *
     * @ORM\Column(name="external", type="string", length=64, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#secrets-configuration-reference
     */
    protected $external;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Project", inversedBy="secrets")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\Docker\Service", mappedBy="secrets")
     */
    protected $services;

    public function __construct()
    {
        $this->services = new Collections\ArrayCollection();
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
        $this->services[] = $service;

        return $this;
    }

    public function removeService(Service $service)
    {
        $this->services->removeElement($service);
    }

    /**
     * @return Service[]|Collections\ArrayCollection
     */
    public function getServices()
    {
        return $this->services;
    }

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }
}
