<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="service_type")
 * @ORM\Entity(repositoryClass="DashtainerBundle\Repository\ServiceTypeRepository")
 */
class ServiceType implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\ServiceCategory", inversedBy="service_types")
     * @ORM\JoinColumn(name="service_category_id", referencedColumnName="id", nullable=false)
     */
    protected $service_category;

    /**
     * @ORM\OneToMany(targetEntity="DashtainerBundle\Entity\Service", mappedBy="service_type")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $services;

    /**
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     */
    protected $is_public;

    /**
     * @ORM\Column(name="`order`", type="smallint")
     */
    protected $order;

    public function __construct()
    {
        $this->services = new Collections\ArrayCollection();
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

    /**
     * @param Service $service
     */
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
}
