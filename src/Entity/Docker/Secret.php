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
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#secrets-configuration-reference
     */
    protected $file;

    /**
     * Only used if $file is set
     *
     * @ORM\Column(name="contents", type="enc_blob", nullable=true)
     */
    protected $contents;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Project", inversedBy="secrets")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServiceSecret", mappedBy="project_secret")
     */
    protected $service_secrets;

    public function __construct()
    {
        $this->service_secrets = new Collections\ArrayCollection();
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
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param string $contents
     * @return $this
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

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

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }

    /**
     * @param ServiceSecret $serviceSecret
     * @return $this
     */
    public function addServiceSecret(ServiceSecret $serviceSecret)
    {
        $this->service_secrets[] = $serviceSecret;

        return $this;
    }

    public function removeServiceSecret(ServiceSecret $serviceSecret)
    {
        $this->service_secrets->removeElement($serviceSecret);
    }

    /**
     * @return ServiceSecret[]|Collections\ArrayCollection
     */
    public function getServiceSecrets()
    {
        return $this->service_secrets;
    }
}
