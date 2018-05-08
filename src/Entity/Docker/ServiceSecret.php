<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service_secret")
 * @ORM\Entity()
 */
class ServiceSecret implements
    Util\HydratorInterface,
    Entity\EntityBaseInterface
{
    use Util\HydratorTrait;
    use Entity\RandomIdTrait;
    use Entity\EntityBaseTrait;

    /**
     * `source` is not listed here, even though it is part of the Service-Secrets API.
     * It will be fetched from ProjectSecret->getName()
     */

    /**
     * @ORM\Column(name="target", type="string", length=64)
     */
    protected $target;

    /**
     * @ORM\Column(name="uid", type="string", length=32)
     */
    protected $uid = '0';

    /**
     * @ORM\Column(name="gid", type="string", length=32)
     */
    protected $gid = '0';

    /**
     * @ORM\Column(name="mode", type="string", length=4)
     */
    protected $mode = '0444';

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Secret", inversedBy="service_secrets")
     * @ORM\JoinColumn(name="project_secret_id", referencedColumnName="id", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#long-syntax-2
     */
    protected $project_secret;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service", inversedBy="secrets")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=false)
     */
    protected $service;

    public function getSource() : ?string
    {
        if (empty($this->getProjectSecret())) {
            return null;
        }

        return $this->getProjectSecret()->getName();
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

    public function getUid() : ?string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return $this
     */
    public function setUid(string $uid)
    {
        $this->uid = $uid;

        return $this;
    }

    public function getGid() : ?string
    {
        return $this->gid;
    }

    /**
     * @param string $gid
     * @return $this
     */
    public function setGid(string $gid)
    {
        $this->gid = $gid;

        return $this;
    }

    public function getMode() : ?string
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

    public function getProjectSecret() : ?Secret
    {
        return $this->project_secret;
    }

    /**
     * @param Secret $project_secret
     * @return $this
     */
    public function setProjectSecret(Secret $project_secret)
    {
        $this->project_secret = $project_secret;

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
    public function setService(Service $service)
    {
        $this->service = $service;

        return $this;
    }
}
