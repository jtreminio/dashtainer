<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_type")
 * @ORM\Entity()
 */
class DockerServiceType implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64, unique=true)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerServiceCategory", inversedBy="types")
     * @ORM\JoinColumn(name="service_category_id", referencedColumnName="id", nullable=false)
     */
    protected $category;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     */
    protected $is_public;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerServiceTypeMeta", mappedBy="type", fetch="EAGER")
     */
    protected $meta;

    /**
     * @ORM\Column(name="`order`", type="smallint")
     */
    protected $order;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerService", mappedBy="type")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $services;

    /**
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    protected $slug;

    /**
     * @ORM\Column(name="versions", type="simple_array", nullable=true)
     */
    protected $versions = [];

    public function __construct()
    {
        $this->meta     = new Collections\ArrayCollection();
        $this->services = new Collections\ArrayCollection();
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

    public function getIsPublic() : ?bool
    {
        return $this->is_public;
    }

    /**
     * @param bool $isPublic
     * @return $this
     */
    public function setIsPublic(bool $isPublic)
    {
        $this->is_public = $isPublic;

        return $this;
    }

    public function getOrder() : ?int
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return $this
     */
    public function setOrder(int $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getCategory() : ?DockerServiceCategory
    {
        return $this->category;
    }

    /**
     * @param DockerServiceCategory $service_category
     * @return $this
     */
    public function setCategory(DockerServiceCategory $service_category)
    {
        $this->category = $service_category;

        return $this;
    }

    /**
     * @param DockerServiceTypeMeta $service_type_meta
     * @return $this
     */
    public function addMeta(DockerServiceTypeMeta $service_type_meta)
    {
        $this->meta[] = $service_type_meta;

        return $this;
    }

    public function removeMeta(DockerServiceTypeMeta $service_type_meta)
    {
        $this->meta->removeElement($service_type_meta);
    }

    public function getMeta(string $name) : ?DockerServiceTypeMeta
    {
        foreach ($this->getMetas() as $meta) {
            if ($meta->getName() === $name) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @return DockerServiceTypeMeta[]|Collections\ArrayCollection
     */
    public function getMetas()
    {
        return $this->meta;
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

    public function getSlug() : string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function setSlug(string $slug)
    {
        $this->slug = $slug;

        return $this;
    }

    public function getVersions() : ?array
    {
        return $this->versions;
    }

    /**
     * @param array $versions
     * @return $this
     */
    public function setVersions(array $versions = null)
    {
        $this->versions = $versions;

        return $this;
    }
}
