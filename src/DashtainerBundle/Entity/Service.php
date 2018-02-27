<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="DashtainerBundle\Repository\ServiceRepository")
 */
class Service implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\ServiceType", inversedBy="services")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $service_type;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\Project", inversedBy="services")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    public function getServiceType() : ?ServiceType
    {
        return $this->service_type;
    }

    /**
     * @param ServiceType $serviceType
     * @return $this
     */
    public function setServiceType(ServiceType $serviceType)
    {
        $this->service_type = $serviceType;

        return $this;
    }

    public function getProject() : ?Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function setProject(Project $project)
    {
        $this->project = $project;

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

    public function getSlug(): string
    {
        return Transliterator::urlize($this->getName());
    }
}
