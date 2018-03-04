<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_type_meta")
 * @ORM\Entity()
 */
class DockerServiceTypeMeta implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64, unique=false)
     */
    protected $name;

    /**
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    protected $data = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerServiceType", inversedBy="service_type_meta")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $service_type;

    public function getData() : ?array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data = null)
    {
        $this->data = $data;

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

    public function getServiceType() : ?DockerServiceType
    {
        return $this->service_type;
    }

    /**
     * @param DockerServiceType $serviceType
     * @return $this
     */
    public function setServiceType(DockerServiceType $serviceType)
    {
        $this->service_type = $serviceType;

        return $this;
    }
}
