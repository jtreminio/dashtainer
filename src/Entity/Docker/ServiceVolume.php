<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_volume")
 * @ORM\Entity()
 */
class ServiceVolume implements
    Util\HydratorInterface,
    Entity\EntityBaseInterface,
    Entity\SlugInterface
{
    use Util\HydratorTrait;
    use Entity\RandomIdTrait;
    use Entity\EntityBaseTrait;

    public const CONSISTENCY_CACHED     = 'cached';
    public const CONSISTENCY_CONSISTENT = 'consistent';
    public const CONSISTENCY_DELEGATED  = 'delegated';

    public const FILETYPE_FILE  = 'file';
    public const FILETYPE_OTHER = 'other';

    public const TYPE_BIND   = 'bind';
    public const TYPE_TMPFS  = 'tmpfs';
    public const TYPE_VOLUME = 'volume';

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
     * @ORM\Column(name="prepend", type="boolean")
     */
    protected $prepend = false;

    /**
     * Only used in MacOS hosts.
     *
     * @ORM\Column(name="consistency", type="string", length=10, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#caching-options-for-volume-mounts-docker-for-mac
     */
    protected $consistency = 'default';

    /**
     * Only set if $filetype == file
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $data;

    /**
     * One of file, other
     *
     * "file" is always type "bind" (local), may or may not have data
     * "other" can be a directory or a file, type "bind" or "volume", no data
     *
     * @ORM\Column(name="filetype", type="string", length=32)
     */
    protected $filetype;

    /**
     * @ORM\Column(name="highlight", type="string", length=16, nullable=true)
     */
    protected $highlight;

    /**
     * @ORM\Column(name="is_internal", type="boolean")
     */
    protected $is_internal = false;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Volume", inversedBy="service_volumes")
     * @ORM\JoinColumn(name="project_volume_id", referencedColumnName="id", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#volumes
     */
    protected $project_volume;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service", inversedBy="volumes")
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
    public function setData(string $data = null)
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

    public function getIsInternal() : bool
    {
        return $this->is_internal;
    }

    /**
     * @param bool $is_internal
     * @return $this
     */
    public function setIsInternal(bool $is_internal)
    {
        $this->is_internal = $is_internal;

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

    public function getProjectVolume() : ?Volume
    {
        return $this->project_volume;
    }

    /**
     * @param Volume $project_volume
     * @return $this
     */
    public function setProjectVolume(Volume $project_volume = null)
    {
        if ($this->project_volume === $project_volume) {
            return $this;
        }

        $this->project_volume = $project_volume;

        if ($project_volume) {
            $project_volume->addServiceVolume($this);
        }

        return $this;
    }

    public function getService() : ?Service
    {
        return $this->service;
    }

    /**
     * @param Service $service
     * @return $this
     */
    public function setService(Service $service = null)
    {
        if ($this->service === $service) {
            return $this;
        }

        $this->service = $service;

        if ($service) {
            $service->addVolume($this);
        }

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
    public function setTarget(string $target = null)
    {
        $this->target = $target;

        return $this;
    }

    public function getPrepend() : bool
    {
        return $this->prepend;
    }

    /**
     * @param bool $prepend
     * @return $this
     */
    public function setPrepend(bool $prepend = false)
    {
        $this->prepend = $prepend;

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
        $this->type = $type;

        return $this;
    }
}
