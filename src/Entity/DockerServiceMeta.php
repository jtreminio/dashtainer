<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_meta")
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
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    protected $data = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerService", inversedBy="service_meta")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

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
}
