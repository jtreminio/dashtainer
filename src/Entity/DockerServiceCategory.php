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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(name="`order`", type="smallint")
     */
    protected $order;

    /**
     * @ORM\OneToMany(targetEntity="DockerServiceType", mappedBy="service_category")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    protected $service_types;

    public function __construct()
    {
        $this->service_types = new Collections\ArrayCollection();
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
    public function addServiceType(DockerServiceType $serviceType)
    {
        $this->service_types[] = $serviceType;

        return $this;
    }

    public function removeServiceType(DockerServiceType $serviceType)
    {
        $this->service_types->removeElement($serviceType);
    }

    /**
     * @return DockerService[]|Collections\ArrayCollection
     */
    public function getServiceTypes()
    {
        return $this->service_types;
    }
}
