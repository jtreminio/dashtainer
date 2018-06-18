<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_port")
 * @ORM\Entity()
 */
class ServicePort implements Util\HydratorInterface, Entity\EntityBaseInterface
{
    use Util\HydratorTrait;
    use Entity\EntityBaseTrait;

    public const MODE_HOST    = 'host';
    public const MODE_INGRESS = 'ingress';

    public const PROTOCOL_TCP = 'tcp';
    public const PROTOCOL_UDP = 'udp';

    /**
     * @ORM\Column(name="published", type="string", length=32)
     */
    protected $published;

    /**
     * @ORM\Column(name="target", type="string", length=32)
     */
    protected $target;

    /**
     * @ORM\Column(name="protocol", type="string", length=3)
     */
    protected $protocol = self::PROTOCOL_TCP;

    /**
     * @ORM\Column(name="mode", type="string", length=7)
     */
    protected $mode = self::MODE_HOST;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service", inversedBy="ports")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

    public function getPublished() : ?string
    {
        return $this->published;
    }

    /**
     * @param string $published
     * @return $this
     */
    public function setPublished(string $published = null)
    {
        $this->published = $published;

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

    public function getProtocol() : string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     * @return $this
     */
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getMode() : string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode(string $mode)
    {
        $this->mode = $mode;

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
            $service->addPort($this);
        }

        return $this;
    }
}
