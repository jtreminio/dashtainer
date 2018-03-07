<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_volume")
 * @ORM\Entity()
 */
class DockerServiceVolume implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    public const PROPOGATION_CACHED     = 'cached';
    public const PROPOGATION_CONSISTENT = 'consistent';
    public const PROPOGATION_DELEGATED  = 'delegated';

    protected const ALLOWED_PROPOGATIONS = [
        self::PROPOGATION_CACHED,
        self::PROPOGATION_CONSISTENT,
        self::PROPOGATION_DELEGATED,
    ];

    public const OWNER_USER   = 'user';
    public const OWNER_SYSTEM = 'system';

    protected const ALLOWED_OWNERS = [
        self::OWNER_USER,
        self::OWNER_SYSTEM,
    ];

    public const TYPE_DIR  = 'directory';
    public const TYPE_FILE = 'file';

    protected const ALLOWED_TYPES = [
        self::TYPE_DIR,
        self::TYPE_FILE,
    ];

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    protected $name;

    /**
     * @ORM\Column(name="source", type="string", length=255)
     */
    protected $source;

    /**
     * @ORM\Column(name="target", type="string", length=255)
     */
    protected $target;

    /**
     * Only used in MacOS hosts.
     *
     * @ORM\Column(name="propogation", type="string", length=10)
     * @see https://docs.docker.com/compose/compose-file/#caching-options-for-volume-mounts-docker-for-mac
     */
    protected $propogation = 'default';

    /**
     * Only set if $type is file
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $data;

    /**
     * One of system, user
     *
     * @ORM\Column(name="owner", type="string", length=6)
     */
    protected $owner = 'system';

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerService", inversedBy="volumes")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

    /**
     * One of file, directory
     *
     * @ORM\Column(name="type", type="string", length=9)
     */
    protected $type;

    public function getData() : ?string
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return $this
     */
    public function setData(string $data)
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

    public function getOwner() : string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     * @return $this
     */
    public function setOwner(string $owner)
    {
        if (!in_array($owner, static::ALLOWED_OWNERS)) {
            throw new \UnexpectedValueException();
        }

        $this->owner = $owner;

        return $this;
    }

    public function getPropogation() : ?string
    {
        return $this->propogation;
    }

    /**
     * @param string $propogation
     * @return $this
     */
    public function setPropogation(string $propogation)
    {
        if (!in_array($propogation, static::ALLOWED_PROPOGATIONS)) {
            throw new \UnexpectedValueException();
        }

        $this->propogation = $propogation;

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
    public function setService(DockerService $service)
    {
        $this->service = $service;

        return $this;
    }

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }

    public function getSource() : ?string
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return $this
     */
    public function setSource(string $source)
    {
        $this->source = $source;

        return $this;
    }

    public function getTarget() : ?string
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget(string $target)
    {
        $this->target = $target;

        return $this;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        if (!in_array($type, static::ALLOWED_TYPES)) {
            throw new \UnexpectedValueException();
        }

        $this->type = $type;

        return $this;
    }
}
