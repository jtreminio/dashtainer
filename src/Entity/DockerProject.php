<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_project")
 * @ORM\Entity()
 */
class DockerProject implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\User", inversedBy="projects")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerNetwork", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $networks;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerSecret", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $secrets;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerService", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $services;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerVolume", mappedBy="project")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $volumes;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    public function __construct()
    {
        $this->networks = new Collections\ArrayCollection();
        $this->secrets  = new Collections\ArrayCollection();
        $this->services = new Collections\ArrayCollection();
        $this->volumes  = new Collections\ArrayCollection();
    }

    public function getUser() : ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param DockerNetwork $network
     * @return $this
     */
    public function addNetwork(DockerNetwork $network)
    {
        $this->networks[] = $network;

        return $this;
    }

    public function removeNetwork(DockerNetwork $network)
    {
        $this->networks->removeElement($network);
    }

    /**
     * @return DockerNetwork[]|Collections\ArrayCollection
     */
    public function getNetworks()
    {
        return $this->networks;
    }

    /**
     * @param DockerSecret $secret
     * @return $this
     */
    public function addSecret(DockerSecret $secret)
    {
        $this->secrets[] = $secret;

        return $this;
    }

    public function removeSecret(DockerSecret $secret)
    {
        $this->secrets->removeElement($secret);
    }

    /**
     * @return DockerSecret[]|Collections\ArrayCollection
     */
    public function getSecrets()
    {
        return $this->secrets;
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

    /**
     * @param DockerVolume $volume
     * @return $this
     */
    public function addVolume(DockerVolume $volume)
    {
        $this->volumes[] = $volume;

        return $this;
    }

    public function removeVolume(DockerVolume $volume)
    {
        $this->volumes->removeElement($volume);
    }

    /**
     * @return DockerVolume[]|Collections\ArrayCollection
     */
    public function getVolumes()
    {
        return $this->volumes;
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

    public function getSlug(): string
    {
        return Transliterator::urlize($this->getName());
    }
}
