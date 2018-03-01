<?php

namespace Dashtainer\Docker;

use Dashtainer\Entity;
use Dashtainer\Util\YamlTag;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

abstract class HandlerAbstract
{
    protected const SERVICE_TYPE = null;

    protected $binDir;

    protected $extraServiceConfigs = [];

    protected $projectName;

    /** @var Entity\DashtainerServiceConfig */
    protected $pyServiceConfig;

    protected $sourceDir;

    protected $targetDir;

    protected $templating;

    abstract public function getDockerConfig() : array;

    abstract protected function getVolumes() : array;

    public function __construct(
        EngineInterface $templating,
        string $binDir,
        string $sourceDirBase
    ) {
        $this->templating    = $templating;
        $this->binDir        = $binDir;

        $serviceType     = static::SERVICE_TYPE;
        $this->sourceDir = "{$sourceDirBase}/{$serviceType}";
    }

    public function setDashtainerServiceConfig(Entity\DashtainerServiceConfig $pyServiceConfig)
    {
        $this->pyServiceConfig = $pyServiceConfig;

        return $this;
    }

    public function setProjectName(string $projectName)
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function setTargetDirBase(string $targetDirBase)
    {
        $this->targetDir = "{$targetDirBase}/{$this->pyServiceConfig->getName()}";;

        return $this;
    }

    /**
     * @param Entity\DashtainerServiceConfig $config
     * @return $this
     */
    public function addExtraServiceConfig(
        Entity\DashtainerServiceConfig $config
    ) {
        $this->extraServiceConfigs []= $config;

        return $this;
    }

    public function getExtraServiceConfigs() : array
    {
        return $this->extraServiceConfigs;
    }

    public function getServiceType() : string
    {
        return static::SERVICE_TYPE;
    }

    protected function getEnvironment() : array
    {
        $environment = [];

        foreach ($this->pyServiceConfig->getEnvironment() as $key => $value) {
            $environment []= "{$key}={$value}";
        }

        return $environment;
    }

    protected function isToolEnabled(string $tool) : bool
    {
        return !empty($this->pyServiceConfig->getSettings()['tools'][$tool]['install']);
    }

    protected function parseVolumes(array $volumes) : array
    {
        $parsedVolumes = [];
        foreach (array_merge($volumes, $this->pyServiceConfig->getVolumes()) as $volume) {
            $parsedVolumes []= $this->joinVolume($volume);
        }

        return $parsedVolumes;
    }

    protected function joinVolume(array $volume) : string
    {
        $configuration = !empty($volume['configuration'])
            ? $volume['configuration']
            : 'cached';

        $mount = "{$volume['source']}:{$volume['target']}:{$configuration}";

        return YamlTag::doubleQuotes($mount);
    }
}
