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

    public const CONSISTENCY_CACHED     = 'cached';
    public const CONSISTENCY_CONSISTENT = 'consistent';
    public const CONSISTENCY_DELEGATED  = 'delegated';

    protected const ALLOWED_CONSISTENCIES = [
        self::CONSISTENCY_CACHED,
        self::CONSISTENCY_CONSISTENT,
        self::CONSISTENCY_DELEGATED,
    ];

    public const OWNER_USER   = 'user';
    public const OWNER_SYSTEM = 'system';

    protected const ALLOWED_OWNERS = [
        self::OWNER_USER,
        self::OWNER_SYSTEM,
    ];

    public const FILETYPE_DIR  = 'directory';
    public const FILETYPE_FILE = 'file';

    protected const ALLOWED_FILETYPES = [
        self::FILETYPE_DIR,
        self::FILETYPE_FILE,
    ];

    public const TYPE_BIND   = 'bind';
    public const TYPE_TMPFS  = 'tmpfs';
    public const TYPE_VOLUME = 'volume';

    protected const ALLOWED_TYPES = [
        self::TYPE_BIND,
        self::TYPE_TMPFS,
        self::TYPE_VOLUME,
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
     * @ORM\Column(name="target", type="string", length=255, nullable=true)
     */
    protected $target;

    /**
     * Only used in MacOS hosts.
     *
     * @ORM\Column(name="consistency", type="string", length=10, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#caching-options-for-volume-mounts-docker-for-mac
     */
    protected $consistency = 'default';

    /**
     * Only set if $type is file
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $data;

    /**
     * One of file, directory
     *
     * @ORM\Column(name="filetype", type="string", length=9)
     */
    protected $filetype;

    /**
     * @ORM\Column(name="highlight", type="string", length=16, nullable=true)
     */
    protected $highlight;

    /**
     * One of system, user
     *
     * @ORM\Column(name="owner", type="string", length=6)
     */
    protected $owner = 'system';

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerVolume", inversedBy="service_volumes")
     * @ORM\JoinColumn(name="project_volume_id", referencedColumnName="id", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#volumes
     */
    protected $project_volume;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerService", inversedBy="volumes")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

    /**
     * One of volume, bind, tmpfs
     *
     * @ORM\Column(name="type", type="string", length=6)
     */
    protected $type = 'bind';

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

    public function getConsistency() : ?string
    {
        return $this->consistency;
    }

    /**
     * @param string $consistency
     * @return $this
     */
    public function setConsistency(string $consistency = null)
    {
        if ($consistency && !in_array($consistency, static::ALLOWED_CONSISTENCIES)) {
            throw new \UnexpectedValueException();
        }

        $this->consistency = $consistency;

        return $this;
    }

    public function getFiletype() : ?string
    {
        return $this->filetype;
    }

    /**
     * @param string $filetype
     * @return $this
     */
    public function setFiletype(string $filetype)
    {
        if (!in_array($filetype, static::ALLOWED_FILETYPES)) {
            throw new \UnexpectedValueException();
        }

        $this->filetype = $filetype;

        return $this;
    }

    public function getHighlight() : ?string
    {
        return $this->highlight;
    }

    /**
     * @param string $highlight
     * @return $this
     */
    public function setHighlight(string $highlight)
    {
        $this->highlight = $highlight;

        return $this;
    }

    public function getProjectVolume() : ?DockerVolume
    {
        return $this->project_volume;
    }

    /**
     * @param DockerVolume $project_volume
     * @return $this
     */
    public function setProjectVolume(DockerVolume $project_volume = null)
    {
        $this->project_volume = $project_volume;

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
