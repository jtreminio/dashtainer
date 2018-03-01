<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_secret")
 * @ORM\Entity()
 */
class DockerSecret implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\DockerProject", inversedBy="secrets")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * If false|null, not used in docker file
     *
     * If true:
     * secrets:
     *   my_first_secret:
     *     external: true
     *
     * If string:
     * secrets:
     *   my_first_secret:
     *     external:
     *       name: redis_secret
     *
     * @ORM\Column(name="external", type="string", length=64, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#secrets-configuration-reference
     */
    protected $external;

    public function getProject() : ?DockerProject
    {
        return $this->project;
    }

    /**
     * @param DockerProject $project
     * @return $this
     */
    public function setProject(DockerProject $project)
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

    /**
     * @return bool|string
     */
    public function getExternal()
    {
        if (empty($this->external)) {
            return null;
        }

        if ($this->external === true || $this->external === 'true') {
            return true;
        }

        return $this->external;
    }

    /**
     * @param bool|string $external
     * @return $this
     */
    public function setExternal($external = null)
    {
        $this->external = empty($external)
            ? null
            : $external;

        return $this;
    }

    public function getSlug(): string
    {
        return Transliterator::urlize($this->getName());
    }
}
