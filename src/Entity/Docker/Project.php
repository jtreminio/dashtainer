<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_project")
 * @ORM\Entity()
 */
class Project implements
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
     * Writes to .env file
     * @ORM\Column(name="environment", type="json_array", nullable=true)
     */
    protected $environment = [];

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\Network", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $networks;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\Secret", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $secrets;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\Service", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $services;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\User", inversedBy="projects")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\Volume", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $volumes;

    public function __construct()
    {
        $this->networks = new Collections\ArrayCollection();
        $this->secrets  = new Collections\ArrayCollection();
        $this->services = new Collections\ArrayCollection();
        $this->volumes  = new Collections\ArrayCollection();
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

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addEnvironment(string $key, string $value = null)
    {
        $this->environment[$key] = $value;

        return $this;
    }

    public function getEnvironments() : array
    {
        return $this->environment;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setEnvironments(array $arr)
    {
        $this->environment = $arr;

        return $this;
    }

    public function removeEnvironment(string $key)
    {
        unset($this->environment[$key]);
    }

    /**
     * @param Network $network
     * @return $this
     */
    public function addNetwork(Network $network)
    {
        if ($this->networks->contains($network)) {
            return $this;
        }

        $this->networks->add($network);
        $network->setProject($this);

        return $this;
    }

    public function removeNetwork(Network $network)
    {
        if (!$this->networks->contains($network)) {
            return;
        }

        $this->networks->removeElement($network);
        $network->setProject(null);
    }

    /**
     * @return Network[]|Collections\ArrayCollection
     */
    public function getNetworks()
    {
        return $this->networks;
    }

    /**
     * @param Secret $secret
     * @return $this
     */
    public function addSecret(Secret $secret)
    {
        if ($this->secrets->contains($secret)) {
            return $this;
        }

        $this->secrets->add($secret);
        $secret->setProject($this);

        return $this;
    }

    public function removeSecret(Secret $secret)
    {
        if (!$this->secrets->contains($secret)) {
            return;
        }

        $this->secrets->removeElement($secret);
        $secret->setProject(null);
    }

    /**
     * @return Secret[]|Collections\ArrayCollection
     */
    public function getSecrets()
    {
        return $this->secrets;
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
        $service->setProject($this);

        return $this;
    }

    public function removeService(Service $service)
    {
        if (!$this->services->contains($service)) {
            return;
        }

        $this->services->removeElement($service);
        $service->setProject(null);
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
        return strtolower(preg_replace("/[^A-Za-z]/", '', $this->getName()));
    }

    public function getUser() : ?Entity\User
    {
        return $this->user;
    }

    /**
     * @param Entity\User $user
     * @return $this
     */
    public function setUser(Entity\User $user = null)
    {
        if ($this->user === $user) {
            return $this;
        }

        $this->user = $user;

        if ($user) {
            $user->addProject($this);
        }

        return $this;
    }

    /**
     * @param Volume $volume
     * @return $this
     */
    public function addVolume(Volume $volume)
    {
        if ($this->volumes->contains($volume)) {
            return $this;
        }

        $this->volumes->add($volume);
        $volume->setProject($this);

        return $this;
    }

    public function removeVolume(Volume $volume)
    {
        if (!$this->volumes->contains($volume)) {
            return;
        }

        $this->volumes->removeElement($volume);
        $volume->setProject(null);
    }

    /**
     * @return Volume[]|Collections\ArrayCollection
     */
    public function getVolumes()
    {
        return $this->volumes;
    }
}
