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
     * @ORM\ManyToOne(targetEntity="DockerServiceType", inversedBy="service_type_meta")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $service_type;

    /**
     * @ORM\Column(name="value", type="json_array", nullable=true)
     */
    protected $value = [];

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

    public function getValue() : ?array
    {
        return $this->value;
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setValue(array $value = null)
    {
        $this->value = $value;

        return $this;
    }
}
