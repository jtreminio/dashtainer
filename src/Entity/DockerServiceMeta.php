<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_type_meta")
 * @ORM\Entity()
 */
class DockerServiceMeta implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Column(name="name", type="string", length=64, unique=false)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerService", inversedBy="service_meta")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

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

    public function getService() : ?DockerService
    {
        return $this->service;
    }

    /**
     * @param DockerService $service
     * @return $this
     */
    public function setDockerService(DockerService $service)
    {
        $this->service = $service;

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
