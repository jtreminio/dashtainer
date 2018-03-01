<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="docker_service")
 * @ORM\Entity(repositoryClass="DashtainerBundle\Repository\DockerServiceRepository")
 */
class DockerService implements Util\HydratorInterface, EntityBaseInterface, SlugInterface
{
    use Util\HydratorTrait;
    use RandomIdTrait;
    use EntityBaseTrait;

    public const PROPOGATION_CACHED     = 'cached';
    public const PROPOGATION_CONSISTENT = 'consistent';
    public const PROPOGATION_DELEGATED  = 'delegated';

    public const RESTART_NO             = 'no';
    public const RESTART_ALWAYS         = 'always';
    public const RESTART_ON_FAILURE     = 'on-failure';
    public const RESTART_UNLESS_STOPPED = 'unless-stopped';

    protected const ALLOWED_PROPOGATIONS = [
        self::PROPOGATION_CACHED,
        self::PROPOGATION_CONSISTENT,
        self::PROPOGATION_DELEGATED,
    ];

    protected const ALLOWED_RESTARTS = [
        self::RESTART_NO,
        self::RESTART_ALWAYS,
        self::RESTART_ON_FAILURE,
        self::RESTART_UNLESS_STOPPED,
    ];

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\DockerServiceType", inversedBy="services")
     * @ORM\JoinColumn(name="service_type_id", referencedColumnName="id", nullable=false)
     */
    protected $service_type;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\DockerProject", inversedBy="services")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(name="build", type="json_array", nullable=true)
     * @uses \DashtainerBundle\Entity\DockerService\Build
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
     * @uses \DashtainerBundle\Entity\DockerService\Deploy
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
     * @uses \DashtainerBundle\Entity\DockerService\Deploy
     * @see https://docs.docker.com/compose/compose-file/#healthcheck
     */
    protected $healthcheck = [];

    /**
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#image
     */
    protected $image;

    /**
     * @ORM\Column(name="isolation", type="string", length=255, nullable=true)
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
     * @uses \DashtainerBundle\Entity\DockerService\Logging
     * @see https://docs.docker.com/compose/compose-file/#logging
     */
    protected $logging = [];

    /**
     * @ORM\Column(name="network_mode", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#network_mode
     */
    protected $network_mode;

    /**
     * @ORM\Column(name="networks", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#networks
     */
    protected $networks = [];

    /**
     * @ORM\Column(name="pid", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#pid
     */
    protected $pid;

    /**
     * @ORM\Column(name="ports", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#ports
     */
    protected $ports = [];

    /**
     * @ORM\Column(name="restart", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#restart
     */
    protected $restart = 'no';

    /**
     * @ORM\Column(name="secrets", type="simple_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#secrets
     */
    protected $secrets = [];

    /**
     * @ORM\Column(name="stop_grace_period", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_grace_period
     */
    protected $stop_grace_period = '10s';

    /**
     * @ORM\Column(name="stop_signal", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#stop_signal
     */
    protected $stop_signal = 'SIGTERM';

    /**
     * @ORM\Column(name="sysctls", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#sysctls
     */
    protected $sysctls = [];

    /**
     * @ORM\Column(name="ulimits", type="json_array", nullable=true)
     * @uses \DashtainerBundle\Entity\DockerService\Ulimits
     * @see https://docs.docker.com/compose/compose-file/#ulimits
     */
    protected $ulimits = [];

    /**
     * @ORM\Column(name="userns_mode", type="string", length=255, nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#userns_mode
     */
    protected $userns_mode;

    /**
     * @ORM\Column(name="volumes", type="json_array", nullable=true)
     * @see https://docs.docker.com/compose/compose-file/#volumes
     */
    protected $volumes = [];

    public function getServiceType() : ?DockerServiceType
    {
        return $this->service_type;
    }

    /**
     * @param DockerServiceType $serviceType
     * @return $this
     */
    public function setServiceType(DockerServiceType $serviceType)
    {
        $this->service_type = $serviceType;

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

    public function getNetworks() : array
    {
        return $this->networks;
    }

    /**
     * @param array $networks
     * @return $this
     */
    public function setNetworks(array $networks)
    {
        $this->networks = $networks;

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

    public function getSecrets() : array
    {
        return $this->secrets;
    }

    /**
     * @param array $secrets
     * @return $this
     */
    public function setSecrets(array $secrets)
    {
        $this->secrets = $secrets;

        return $this;
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
     * @param string      $source
     * @param string      $target
     * @param string|null $propogation
     * @return $this
     */
    public function addVolume(
        string $source,
        string $target,
        string $propogation = null
    ) {

        if (!is_null($propogation) &&
            !in_array($propogation, static::ALLOWED_PROPOGATIONS)
        ) {
            throw new \UnexpectedValueException();
        }

        $volume = "{$source}:{$target}";
        $volume = $propogation
            ? "{$volume}:{$propogation}"
            : $volume;

        $this->volumes[$source] = $volume;

        return $this;
    }

    public function getVolumes() : array
    {
        return $this->volumes;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setVolumes(array $arr)
    {
        $this->volumes = $arr;

        return $this;
    }

    public function removeVolume(string $source)
    {
        unset($this->volumes[$source]);
    }

    public function getSlug(): string
    {
        return Transliterator::urlize($this->getName());
    }
}