<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service")
 * @ORM\Entity()
 */
class DockerService implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

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
     * @uses \Dashtainer\Entity\DockerService\Build
     * @see https://docs.docker.com/compose/compose-file/#build
     */
    protected $build = [];

    /**
     * @ORM\Column(name="command", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#command
     */
    protected $command = [];

    /**
     * @ORM\Column(name="deploy", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\DockerService\Deploy
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
     * @uses \Dashtainer\Entity\DockerService\Deploy
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
     * @uses \Dashtainer\Entity\DockerService\Logging
     * @see https://docs.docker.com/compose/compose-file/#logging
     */
    protected $logging = [];

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerServiceMeta", mappedBy="service", fetch="EAGER")
     */
    protected $meta;

    /**
     * @ORM\Column(name="network_mode", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#network_mode
     */
    protected $network_mode;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\DockerNetwork", inversedBy="services")
     * @ORM\JoinTable(name="docker_services_networks")
     * @see https://docs.docker.com/compose/compose-file/#networks
     */
    protected $networks;

    /**
     * @ORM\Column(name="pid", type="string", length=4, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#pid
     */
    protected $pid;

    /**
     * @ORM\Column(name="ports", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#ports
     */
    protected $ports = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerProject", inversedBy="services")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\DockerVolume", inversedBy="services")
     * @ORM\JoinTable(name="docker_services_project_volumes")
     * @see https://docs.docker.com/compose/compose-file/#volumes
     */
    protected $project_volumes;

    /**
     * @ORM\Column(name="restart", type="string", length=14, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#restart
     */
    protected $restart = 'no';

    /**
     * @ORM\ManyToMany(targetEntity="Dashtainer\Entity\DockerSecret", inversedBy="services")
     * @ORM\JoinTable(name="docker_services_secrets")
     * @see https://docs.docker.com/compose/compose-file/#secrets
     */
    protected $secrets;

    /**
     * @ORM\Column(name="stop_grace_period", type="string", length=12, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_grace_period
     */
    protected $stop_grace_period = '10s';

    /**
     * @ORM\Column(name="stop_signal", type="string", length=12, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_signal
     */
    protected $stop_signal = 'SIGTERM';

    /**
     * @ORM\Column(name="sysctls", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#sysctls
     */
    protected $sysctls = [];

    /**
     * @ORM\ManyToOne(targetEntity="Dashtainer\Entity\DockerServiceType", inversedBy="services")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

    /**
     * @ORM\Column(name="ulimits", type="json_array", nullable=true)
     * @uses \Dashtainer\Entity\DockerService\Ulimits
     * @see https://docs.docker.com/compose/compose-file/#ulimits
     */
    protected $ulimits = [];

    /**
     * @ORM\Column(name="userns_mode", type="string", length=4, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#userns_mode
     */
    protected $userns_mode;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerServiceVolume", mappedBy="service")
     */
    protected $volumes;

    public function __construct()
    {
        $this->networks        = new Collections\ArrayCollection();
        $this->secrets         = new Collections\ArrayCollection();
        $this->meta            = new Collections\ArrayCollection();
        $this->project_volumes = new Collections\ArrayCollection();
        $this->volumes         = new Collections\ArrayCollection();
    }

    public function getBuild() : DockerService\Build
    {
        $build = new DockerService\Build();
        $build->fromArray($this->build);

        return $build;
    }

    /**
     * @param DockerService\Build $build
     * @return $this
     */
    public function setBuild(DockerService\Build $build = null)
    {
        $this->build = $build
            ? $build->toArray()
            : [];

        return $this;
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

    public function getDeploy() : DockerService\Deploy
    {
        $deploy = new DockerService\Deploy();
        $deploy->fromArray($this->deploy);

        return $deploy;
    }

    /**
     * @param DockerService\Deploy $deploy
     * @return $this
     */
    public function setDeploy(DockerService\Deploy $deploy = null)
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

    public function getHealthcheck() : DockerService\Healthcheck
    {
        $healthcheck = new DockerService\Healthcheck();
        $healthcheck->fromArray($this->healthcheck);

        return $healthcheck;
    }

    /**
     * @param DockerService\Healthcheck $healthcheck
     * @return $this
     */
    public function setHealthcheck(DockerService\Healthcheck $healthcheck = null)
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

    public function getLogging() : DockerService\Logging
    {
        $logging = new DockerService\Logging();
        $logging->fromArray($this->logging);

        return $logging;
    }

    /**
     * @param DockerService\Logging $logging
     * @return $this
     */
    public function setLogging(DockerService\Logging $logging = null)
    {
        $this->logging = $logging
            ? $logging->toArray()
            : [];

        return $this;
    }

    /**
     * @param DockerServiceMeta $service_meta
     * @return $this
     */
    public function addMeta(DockerServiceMeta $service_meta)
    {
        $this->meta[] = $service_meta;

        return $this;
    }

    public function removeMeta(DockerServiceMeta $service_meta)
    {
        $this->meta->removeElement($service_meta);
    }

    public function getMeta(string $name) : ?DockerServiceMeta
    {
        foreach ($this->getMetas() as $meta) {
            if ($meta->getName() === $name) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @return DockerServiceMeta[]|Collections\ArrayCollection
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
     * @param DockerNetwork $network
     * @return $this
     */
    public function addNetwork(DockerNetwork $network)
    {
        if ($this->networks->contains($network)) {
            return $this;
        }

        $this->networks->add($network);
        $network->addService($this);

        return $this;
    }

    public function removeNetwork(DockerNetwork $network)
    {
        if (!$this->networks->contains($network)) {
            return;
        }

        $this->networks->removeElement($network);
        $network->removeService($this);
    }

    /**
     * @return DockerNetwork[]|Collections\ArrayCollection
     */
    public function getNetworks()
    {
        return $this->networks;
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

    public function getPorts() : array
    {
        return $this->ports;
    }

    /**
     * @param array $ports
     * @return $this
     */
    public function setPorts(array $ports)
    {
        $this->ports = $ports;

        return $this;
    }

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

    /**
     * @param DockerVolume $project_volume
     * @return $this
     */
    public function addProjectVolume(DockerVolume $project_volume)
    {
        $this->project_volumes[] = $project_volume;

        return $this;
    }

    public function removeProjectVolume(DockerVolume $project_volume)
    {
        $this->project_volumes->removeElement($project_volume);
    }

    /**
     * @return DockerVolume[]|Collections\ArrayCollection
     */
    public function getProjectVolumes()
    {
        return $this->project_volumes;
    }

    public function getRestart() : string
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
     * @param DockerSecret $secret
     * @return $this
     */
    public function addSecret(DockerSecret $secret)
    {
        $this->secrets[] = $secret;

        return $this;
    }

    public function removeSecret(DockerSecret $secret)
    {
        $this->secrets->removeElement($secret);
    }

    /**
     * @return DockerSecret[]|Collections\ArrayCollection
     */
    public function getSecrets()
    {
        return $this->secrets;
    }

    public function getSlug() : string
    {
        return Transliterator::urlize($this->getName());
    }

    public function getStopGracePeriod() : string
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

    public function getStopSignal() : string
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

    public function getType() : ?DockerServiceType
    {
        return $this->type;
    }

    /**
     * @param DockerServiceType $serviceType
     * @return $this
     */
    public function setType(DockerServiceType $serviceType)
    {
        $this->type = $serviceType;

        return $this;
    }

    public function getUlimits() : DockerService\Ulimits
    {
        $ulimits = new DockerService\Ulimits();
        $ulimits->fromArray($this->ulimits);

        return $ulimits;
    }

    /**
     * @param DockerService\Ulimits $ulimits
     * @return $this
     */
    public function setUlimits(DockerService\Ulimits $ulimits = null)
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

    /**
     * @param DockerServiceVolume $volume
     * @return $this
     */
    public function addVolume(DockerServiceVolume $volume)
    {
        $this->volumes[] = $volume;

        return $this;
    }

    public function removeVolume(DockerServiceVolume $volume)
    {
        $this->volumes->removeElement($volume);
    }

    /**
     * @return DockerServiceVolume[]|Collections\ArrayCollection
     */
    public function getVolumes()
    {
        return $this->volumes;
    }
}
