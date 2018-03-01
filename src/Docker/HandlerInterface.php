<?php

namespace Dashtainer\Docker;

use Dashtainer\Entity;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

interface HandlerInterface
{
    public function __construct(
        EngineInterface $templating,
        string $binDir,
        string $sourceDirBase
    );

    /**
     * @return Entity\DashtainerServiceConfig[]
     */
    public function getExtraServiceConfigs() : array;

    public function getDockerConfig() : array;

    public function getServiceType() : string;

    /**
     * @param Entity\DashtainerServiceConfig $config
     * @return $this
     */
    public function setDashtainerServiceConfig(Entity\DashtainerServiceConfig $config);

    /**
     * @param string $projectName
     * @return $this
     */
    public function setProjectName(string $projectName);

    /**
     * @param string $targetDirBase
     * @return $this
     */
    public function setTargetDirBase(string $targetDirBase);

    public function writeFiles();
}
