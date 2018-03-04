<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_volume")
 * @ORM\Entity()
 */
class DockerServiceVolume implements Util\HydratorInterface, EntityBaseInterface
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

    public const TYPE_DIR  = 'directory';
    public const TYPE_FILE = 'file';

    protected const ALLOWED_TYPES = [
        self::TYPE_DIR,
        self::TYPE_FILE,
    ];

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
     * @ORM\Column(name="data", type="blob", nullable=true)
     */
    protected $data;

    /**
     * @ORM\Column(name="is_removable", type="boolean")
     */
    protected $is_removable = true;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerService", inversedBy="service_volumes")
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

    public function getIsRemovable() : bool
    {
        return $this->is_removable;
    }

    /**
     * @param bool $is_removable
     * @return $this
     */
    public function setIsRemovable(bool $is_removable)
    {
        $this->is_removable = $is_removable;

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
        $this->type = $type;

        return $this;
    }
}
