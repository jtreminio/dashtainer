<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_category")
 * @ORM\Entity()
 */
class DockerServiceCategory implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(name="`order`", type="smallint")
     */
    protected $order;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerServiceType", mappedBy="category")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    protected $types;

    public function __construct()
    {
        $this->types = new Collections\ArrayCollection();
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

    /**
     * @param DockerServiceType $serviceType
     * @return $this
     */
    public function addType(DockerServiceType $serviceType)
    {
        $this->types[] = $serviceType;

        return $this;
    }

    public function removeType(DockerServiceType $serviceType)
    {
        $this->types->removeElement($serviceType);
    }

    /**
     * @return DockerService[]|Collections\ArrayCollection
     */
    public function getTypes()
    {
        return $this->types;
    }
}
