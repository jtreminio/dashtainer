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
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    protected $slug;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     */
    protected $is_public;

    /**
     * @ORM\Column(name="`order`", type="smallint")
     */
    protected $order;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerServiceCategory", inversedBy="service_types")
     * @ORM\JoinColumn(name="service_category_id", referencedColumnName="id", nullable=false)
     */
    protected $service_category;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerServiceTypeMeta", mappedBy="service_type", fetch="EAGER")
     */
    protected $service_type_meta;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerService", mappedBy="service_type")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $services;

    /**
     * @ORM\Column(name="versions", type="simple_array", nullable=true)
     */
    protected $versions = [];

    public function __construct()
    {
        $this->services          = new Collections\ArrayCollection();
        $this->service_type_meta = new Collections\ArrayCollection();
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

    public function getServiceCategory() : ?DockerServiceCategory
    {
        return $this->service_category;
    }

    /**
     * @param DockerServiceCategory $service_category
     * @return $this
     */
    public function setServiceCategory(DockerServiceCategory $service_category)
    {
        $this->service_category = $service_category;

        return $this;
    }

    /**
     * @param DockerServiceTypeMeta $service_type_meta
     * @return $this
     */
    public function addServiceTypeMeta(DockerServiceTypeMeta $service_type_meta)
    {
        $this->service_type_meta[] = $service_type_meta;

        return $this;
    }

    public function removeServiceTypeMeta(DockerServiceTypeMeta $service_type_meta)
    {
        $this->service_type_meta->removeElement($service_type_meta);
    }

    public function getServiceTypeMeta(string $name) : ?DockerServiceTypeMeta
    {
        /** @var DockerServiceTypeMeta $meta */
        foreach ($this->service_type_meta as $meta) {
            if ($meta->getName() === $name) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @return DockerServiceTypeMeta[]|Collections\ArrayCollection
     */
    public function getServiceTypeMetas()
    {
        return $this->service_type_meta;
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
