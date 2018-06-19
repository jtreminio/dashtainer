<?php

namespace Dashtainer\Entity\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service")
 * @ORM\Entity()
 */
class Service implements
    Util\HydratorInterface,
    Entity\EntityBaseInterface,
    Entity\SlugInterface
{
    use Util\HydratorTrait;
    use Entity\RandomIdTrait;
    use Entity\EntityBaseTrait;

    public const RESTART_NO             = 'no';
    public const RESTART_ALWAYS         = 'always';
    public const RESTART_ON_FAILURE     = 'on-failure';
    public const RESTART_UNLESS_STOPPED = 'unless-stopped';

    protected const ALLOWED_RESTARTS = [
        self::RESTART_NO,
        self::RESTART_ALWAYS,
        self::RESTART_ON_FAILURE,
        self::RESTART_UNLESS_STOPPED,
    ];

    /**
     * @ORM\Column(name="name", type="string", length=64)
     */
    protected $name;

    /**
     * @ORM\Column(name="build", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\Docker\Service\Build
     * @see https://docs.docker.com/compose/compose-file/#build
     */
    protected $build = [];

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\Service", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\Column(name="command", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#command
     */
    protected $command = [];

    /**
     * @ORM\Column(name="deploy", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\Docker\Service\Deploy
     * @see https://docs.docker.com/compose/compose-file/#deploy
     */
    protected $deploy = [];

    /**
     * @ORM\Column(name="devices", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#devices
     */
    protected $devices = [];

    /**
     * @ORM\Column(name="depends_on", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#depends_on
     */
    protected $depends_on = [];

    /**
     * @ORM\Column(name="dns", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#dns
     */
    protected $dns = [];

    /**
     * @ORM\Column(name="dns_search", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#dns_search
     */
    protected $dns_search = [];

    /**
     * @ORM\Column(name="entrypoint", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#entrypoint
     */
    protected $entrypoint = [];

    /**
     * @ORM\Column(name="env_file", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#env_file
     */
    protected $env_file = [];

    /**
     * @ORM\Column(name="environment", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#environment
     */
    protected $environment = [];

    /**
     * @ORM\Column(name="expose", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#expose
     */
    protected $expose = [];

    /**
     * @ORM\Column(name="extra_hosts", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#extra_hosts
     */
    protected $extra_hosts = [];

    /**
     * @ORM\Column(name="healthcheck", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\Docker\Service\Healthcheck
     * @see https://docs.docker.com/compose/compose-file/#healthcheck
     */
    protected $healthcheck = [];

    /**
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#image
     */
    protected $image;

    /**
     * @ORM\Column(name="isolation", type="string", length=32, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#isolation
     */
    protected $isolation;

    /**
     * @ORM\Column(name="labels", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#labels-2
     */
    protected $labels = [];

    /**
     * @ORM\Column(name="logging", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\Docker\Service\Logging
     * @see https://docs.docker.com/compose/compose-file/#logging
     */
    protected $logging = [];

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServiceMeta",
     *     mappedBy="service", orphanRemoval=true
     * )
     */
    protected $meta;

    /**
     * @ORM\Column(name="network_mode", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#network_mode
     */
    protected $network_mode;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\Docker\Network", inversedBy="services")
     * @ORM\JoinTable(name="docker_services_networks")
     * @see https://docs.docker.com/compose/compose-file/#networks
     */
    protected $networks;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Service", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @ORM\Column(name="pid", type="string", length=4, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#pid
     */
    protected $pid;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServicePort",
     *     mappedBy="service", orphanRemoval=true
     * )
     * @see https://docs.docker.com/compose/compose-file/#ports
     */
    protected $ports;

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\Project", inversedBy="services")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\Column(name="restart", type="string", length=14, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#restart
     */
    protected $restart;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServiceSecret", mappedBy="service")
     */
    protected $secrets;

    /**
     * @ORM\Column(name="stop_grace_period", type="string", length=12, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_grace_period
     */
    protected $stop_grace_period;

    /**
     * @ORM\Column(name="stop_signal", type="string", length=12, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_signal
     */
    protected $stop_signal;

    /**
     * @ORM\Column(name="sysctls", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#sysctls
     */
    protected $sysctls = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\Docker\ServiceType", inversedBy="services")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

    /**
     * @ORM\Column(name="ulimits", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\Docker\Service\Ulimits
     * @see https://docs.docker.com/compose/compose-file/#ulimits
     */
    protected $ulimits = [];

    /**
     * @ORM\Column(name="userns_mode", type="string", length=4, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#userns_mode
     */
    protected $userns_mode;

    /**
     * @ORM\Column(name="version", type="string", length=16, nullable=true)
     */
    protected $version;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\Docker\ServiceVolume", mappedBy="service")
     */
    protected $volumes;

    /**
     * @ORM\Column(name="working_dir", type="string", length=256, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#domainname-hostname-ipc-mac_address-privileged-read_only-shm_size-stdin_open-tty-user-working_dir
     */
    protected $working_dir;

    public function __construct()
    {
        $this->children = new Collections\ArrayCollection();
        $this->meta     = new Collections\ArrayCollection();
        $this->networks = new Collections\ArrayCollection();
        $this->ports    = new Collections\ArrayCollection();
        $this->secrets  = new Collections\ArrayCollection();
        $this->volumes  = new Collections\ArrayCollection();
    }

    public function getBuild() : Service\Build
    {
        $build = new Service\Build();
        $build->fromArray($this->build);

        return $build;
    }

    /**
     * @param Service\Build $build
     * @return $this
     */
    public function setBuild(Service\Build $build = null)
    {
        $this->build = $build
            ? $build->toArray()
            : [];

        return $this;
    }

    /**
     * @param Service $child
     * @return $this
     */
    public function addChild(Service $child)
    {
        if ($this->children->contains($child)) {
            return $this;
        }

        $this->children->add($child);
        $child->setParent($this);

        return $this;
    }

    public function removeChild(Service $child)
    {
        if (!$this->children->contains($child)) {
            return;
        }

        $this->children->removeElement($child);
        $child->setParent(null);
    }

    /**
     * @return Service[]|Collections\ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function getCommand() : array
    {
        return $this->command;
    }

    /**
     * @param array $command
     * @return $this
     */
    public function setCommand(array $command)
    {
        $this->command = $command;

        return $this;
    }

    public function getDeploy() : ?Service\Deploy
    {
        if (empty($this->deploy)) {
            return null;
        }

        $deploy = new Service\Deploy();
        $deploy->fromArray($this->deploy);

        return $deploy;
    }

    /**
     * @param Service\Deploy $deploy
     * @return $this
     */
    public function setDeploy(Service\Deploy $deploy = null)
    {
        $this->deploy = $deploy
            ? $deploy->toArray()
            : [];

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addDevice(string $key, string $value = null)
    {
        $this->devices[$key] = $value;

        return $this;
    }

    public function getDevices() : array
    {
        return $this->devices;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setDevices(array $arr)
    {
        $this->devices = $arr;

        return $this;
    }

    public function removeDevice(string $key)
    {
        unset($this->devices[$key]);
    }

    public function getDependsOn() : array
    {
        return $this->depends_on;
    }

    /**
     * @param array $depends_on
     * @return $this
     */
    public function setDependsOn(array $depends_on)
    {
        $this->depends_on = $depends_on;

        return $this;
    }

    public function getDns() : array
    {
        return $this->dns;
    }

    /**
     * @param array $dns
     * @return $this
     */
    public function setDns(array $dns)
    {
        $this->dns = $dns;

        return $this;
    }

    public function getDnsSearch() : array
    {
        return $this->dns_search;
    }

    /**
     * @param array $dns_search
     * @return $this
     */
    public function setDnsSearch(array $dns_search)
    {
        $this->dns_search = $dns_search;

        return $this;
    }

    public function getEntrypoint() : array
    {
        return $this->entrypoint;
    }

    /**
     * @param array $entrypoint
     * @return $this
     */
    public function setEntrypoint(array $entrypoint)
    {
        $this->entrypoint = $entrypoint;

        return $this;
    }

    public function getEnvFile() : array
    {
        return $this->env_file;
    }

    /**
     * @param array $env_file
     * @return $this
     */
    public function setEnvFile(array $env_file)
    {
        $this->env_file = $env_file;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addEnvironment(string $key, string $value = null)
    {
        $this->environment[$key] = $value;

        return $this;
    }

    public function getEnvironments() : array
    {
        return $this->environment;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setEnvironments(array $arr)
    {
        $this->environment = $arr;

        return $this;
    }

    public function removeEnvironment(string $key)
    {
        unset($this->environment[$key]);
    }

    public function getExpose() : array
    {
        return $this->expose;
    }

    /**
     * @param array $expose
     * @return $this
     */
    public function setExpose(array $expose)
    {
        $this->expose = $expose;

        return $this;
    }

    public function getExtraHosts() : array
    {
        return $this->extra_hosts;
    }

    /**
     * @param array $extra_hosts
     * @return $this
     */
    public function setExtraHosts(array $extra_hosts)
    {
        $this->extra_hosts = $extra_hosts;

        return $this;
    }

    public function getHealthcheck() : Service\Healthcheck
    {
        $healthcheck = new Service\Healthcheck();
        $healthcheck->fromArray($this->healthcheck);

        return $healthcheck;
    }

    /**
     * @param Service\Healthcheck $healthcheck
     * @return $this
     */
    public function setHealthcheck(Service\Healthcheck $healthcheck = null)
    {
        $this->healthcheck = $healthcheck
            ? $healthcheck->toArray()
            : [];

        return $this;
    }

    public function getImage() : ?string
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return $this
     */
    public function setImage(string $image = null)
    {
        $this->image = $image;

        return $this;
    }

    public function getIsolation() : ?string
    {
        return $this->isolation;
    }

    /**
     * @param string $isolation
     * @return $this
     */
    public function setIsolation(string $isolation = null)
    {
        $this->isolation = $isolation;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addLabel(string $key, string $value = null)
    {
        $this->labels[$key] = $value;

        return $this;
    }

    public function getLabels() : array
    {
        return $this->labels;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setLabels(array $arr)
    {
        $this->labels = $arr;

        return $this;
    }

    public function removeLabel(string $key)
    {
        unset($this->labels[$key]);
    }

    public function getLogging() : ?Service\Logging
    {
        if (empty($this->logging)) {
            return null;
        }

        $logging = new Service\Logging();
        $logging->fromArray($this->logging);

        return $logging;
    }

    /**
     * @param Service\Logging $logging
     * @return $this
     */
    public function setLogging(Service\Logging $logging = null)
    {
        $this->logging = $logging
            ? $logging->toArray()
            : [];

        return $this;
    }

    /**
     * @param ServiceMeta $meta
     * @return $this
     */
    public function addMeta(ServiceMeta $meta)
    {
        if ($this->meta->contains($meta)) {
            return $this;
        }

        $this->meta->add($meta);
        $meta->setService($this);

        return $this;
    }

    public function removeMeta(ServiceMeta $meta)
    {
        if (!$this->meta->contains($meta)) {
            return;
        }

        $this->meta->removeElement($meta);
        $meta->setService(null);
    }

    public function getMeta(string $name = null) : ?ServiceMeta
    {
        if (!$name) {
            return null;
        }

        foreach ($this->getMetas() as $meta) {
            if ($meta->getName() === $name) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @return ServiceMeta[]|Collections\ArrayCollection
     */
    public function getMetas()
    {
        return $this->meta;
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

    public function getNetworkMode() : ?string
    {
        return $this->network_mode;
    }

    /**
     * @param string $network_mode
     * @return $this
     */
    public function setNetworkMode(string $network_mode = null)
    {
        $this->network_mode = $network_mode;

        return $this;
    }

    /**
     * @param Network $network
     * @return $this
     */
    public function addNetwork(Network $network)
    {
        if ($this->networks->contains($network)) {
            return $this;
        }

        $this->networks->add($network);
        $network->addService($this);

        return $this;
    }

    public function removeNetwork(Network $network)
    {
        if (!$this->networks->contains($network)) {
            return;
        }

        $this->networks->removeElement($network);
        $network->removeService($this);
    }

    /**
     * @return Network[]|Collections\ArrayCollection
     */
    public function getNetworks()
    {
        return $this->networks;
    }

    public function getParent() : ?Service
    {
        return $this->parent;
    }

    /**
     * @param Service $parent
     * @return $this
     */
    public function setParent(Service $parent = null)
    {
        if ($this->parent === $parent) {
            return $this;
        }

        $this->parent = $parent;

        if ($parent) {
            $parent->addChild($this);
        }

        return $this;
    }

    public function getPid() : ?string
    {
        return $this->pid;
    }

    /**
     * @param string $pid
     * @return $this
     */
    public function setPid(string $pid = null)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @param ServicePort $port
     * @return $this
     */
    public function addPort(ServicePort $port)
    {
        if ($this->ports->contains($port)) {
            return $this;
        }

        $this->ports->add($port);
        $port->setService($this);

        return $this;
    }

    public function removePort(ServicePort $port)
    {
        if (!$this->ports->contains($port)) {
            return;
        }

        $this->ports->removeElement($port);
        $port->setService(null);
    }

    /**
     * @return ServicePort[]|Collections\ArrayCollection
     */
    public function getPorts()
    {
        return $this->ports;
    }

    public function getProject() : ?Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function setProject(Project $project = null)
    {
        if ($this->project === $project) {
            return $this;
        }

        $this->project = $project;

        if ($project) {
            $project->addService($this);
        }

        return $this;
    }

    public function getRestart() : ?string
    {
        return $this->restart;
    }

    /**
     * @param string $restart
     * @return $this
     */
    public function setRestart(string $restart)
    {
        if (!in_array($restart, static::ALLOWED_RESTARTS)) {
            throw new \UnexpectedValueException();
        }

        $this->restart = $restart;

        return $this;
    }

    /**
     * @param ServiceSecret $secret
     * @return $this
     */
    public function addSecret(ServiceSecret $secret)
    {
        if ($this->secrets->contains($secret)) {
            return $this;
        }

        $this->secrets->add($secret);
        $secret->setService($this);

        return $this;
    }

    public function removeSecret(ServiceSecret $secret)
    {
        if (!$this->secrets->contains($secret)) {
            return;
        }

        $this->secrets->removeElement($secret);
        $secret->setService(null);
    }

    public function getSecret(string $name) : ?ServiceSecret
    {
        foreach ($this->getSecrets() as $serviceSecret) {
            $secret = $serviceSecret->getProjectSecret();

            if ($secret->getName() === $name) {
                return $serviceSecret;
            }
        }

        return null;
    }

    /**
     * @return ServiceSecret[]|Collections\ArrayCollection
     */
    public function getSecrets()
    {
        return $this->secrets;
    }

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }

    public function getStopGracePeriod() : ?string
    {
        return $this->stop_grace_period;
    }

    /**
     * @param string $stop_grace_period
     * @return $this
     */
    public function setStopGracePeriod(string $stop_grace_period)
    {
        $this->stop_grace_period = $stop_grace_period;

        return $this;
    }

    public function getStopSignal() : ?string
    {
        return $this->stop_signal;
    }

    /**
     * @param string $stop_signal
     * @return $this
     */
    public function setStopSignal(string $stop_signal)
    {
        $this->stop_signal = $stop_signal;

        return $this;
    }

    /**
     * @param string      $key
     * @param string|null $value
     * @return $this
     */
    public function addSysctl(string $key, string $value = null)
    {
        $this->sysctls[$key] = $value;

        return $this;
    }

    public function getSysctls() : array
    {
        return $this->sysctls;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setSysctls(array $arr)
    {
        $this->sysctls = $arr;

        return $this;
    }

    public function removeSysctl(string $key)
    {
        unset($this->sysctls[$key]);
    }

    public function getType() : ?ServiceType
    {
        return $this->type;
    }

    /**
     * @param ServiceType $serviceType
     * @return $this
     */
    public function setType(ServiceType $serviceType = null)
    {
        if ($this->type === $serviceType) {
            return $this;
        }

        $this->type = $serviceType;

        if ($serviceType) {
            $serviceType->addService($this);
        }

        return $this;
    }

    public function getUlimits() : Service\Ulimits
    {
        $ulimits = new Service\Ulimits();

        if (!empty($this->ulimits['memlock'])) {
            $ulimits->setMemlock(
                $this->ulimits['memlock']['soft'],
                $this->ulimits['memlock']['hard']
            );
        }

        if (!empty($this->ulimits['nofile'])) {
            $ulimits->setNofile(
                $this->ulimits['nofile']['soft'],
                $this->ulimits['nofile']['hard']
            );
        }

        if (array_key_exists('nproc', $this->ulimits)) {
            $ulimits->setNproc($this->ulimits['nproc']);
        }

        return $ulimits;
    }

    /**
     * @param Service\Ulimits $ulimits
     * @return $this
     */
    public function setUlimits(Service\Ulimits $ulimits = null)
    {
        $this->ulimits = $ulimits
            ? $ulimits->toArray()
            : [];

        return $this;
    }

    public function getUsernsMode() : ?string
    {
        return $this->userns_mode;
    }

    /**
     * @param string $userns_mode
     * @return $this
     */
    public function setUsernsMode(string $userns_mode = null)
    {
        $this->userns_mode = $userns_mode;

        return $this;
    }

    public function getVersion() : ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version = null)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param ServiceVolume $volume
     * @return $this
     */
    public function addVolume(ServiceVolume $volume)
    {
        if ($this->volumes->contains($volume)) {
            return $this;
        }

        $this->volumes->add($volume);
        $volume->setService($this);

        return $this;
    }

    public function removeVolume(ServiceVolume $volume)
    {
        if (!$this->volumes->contains($volume)) {
            return;
        }

        $this->volumes->removeElement($volume);
        $volume->setService(null);
    }

    public function getVolume(string $name) : ?ServiceVolume
    {
        foreach ($this->getVolumes() as $volume) {
            if ($volume->getName() === $name) {
                return $volume;
            }
        }

        return null;
    }

    /**
     * @return ServiceVolume[]|Collections\ArrayCollection
     */
    public function getVolumes()
    {
        return $this->volumes;
    }

    public function getWorkingDir() : ?string
    {
        return $this->working_dir;
    }

    /**
     * @param string $working_dir
     * @return $this
     */
    public function setWorkingDir(string $working_dir)
    {
        $this->working_dir = $working_dir;

        return $this;
    }
}
