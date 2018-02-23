<?php

namespace PodYardBundle\Docker;

use PodYardBundle\Entity;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

interface HandlerInterface
{
    public function __construct(
        EngineInterface $templating,
        string $binDir,
        string $sourceDirBase
    );

    /**
     * @return Entity\PodYardServiceConfig[]
     */
    public function getExtraServiceConfigs() : array;

    public function getDockerConfig() : array;

    public function getServiceType() : string;

    /**
     * @param Entity\PodYardServiceConfig $config
     * @return $this
     */
    public function setPodYardServiceConfig(Entity\PodYardServiceConfig $config);

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
