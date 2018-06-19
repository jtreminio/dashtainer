<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_type_meta")
 * @ORM\Entity()
 */
class ServiceTypeMeta implements Util\HydratorInterface, Entity\EntityBaseInterface
{
    use Util\HydratorTrait;
    use Entity\EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64, unique=false)
     */
    protected $name;

    /**
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    protected $data = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\ServiceType", inversedBy="meta")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

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

    public function getType() : ?ServiceType
    {
        return $this->type;
    }

    /**
     * @param ServiceType $serviceType
     * @return $this
     */
    public function setType(ServiceType $serviceType = null)
    {
        if ($this->type === $serviceType) {
            return $this;
        }

        $this->type = $serviceType;

        if ($serviceType) {
            $serviceType->addMeta($this);
        }

        return $this;
    }
}
