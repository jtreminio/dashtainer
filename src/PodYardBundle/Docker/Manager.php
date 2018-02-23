<?php

namespace PodYardBundle\Docker;

use PodYardBundle\Entity;
use PodYardBundle\Util\YamlTag;

class Manager
{
    /** @var Entity\DockerComposeConfig */
    protected $dockerComposeConfig;

    /** @var HandlerInterface[] */
    protected $handlers = [];

    /** @var Entity\PodYardConfig */
    protected $podYardConfig;

    /** @var HandlerInterface[] */
    protected $privateHandlers = [];

    public function __construct()
    {
        $this->dockerComposeConfig = new Entity\DockerComposeConfig();
    }

    /**
     * @param iterable|HandlerInterface[] $handlers
     */
    public function setHandlers(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getServiceType()] = $handler;
        }
    }

    /**
     * For handlers we do not want users to call manually.
     * For example, PHP configures Blackfire automatically if chosen.
     *
     * @param iterable|HandlerInterface[] $handlers
     */
    public function setPrivateHandlers(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->privateHandlers[$handler->getServiceType()] = $handler;
        }
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setPodYardConfig(array $config)
    {
        $this->podYardConfig = new Entity\PodYardConfig($config);

        return $this;
    }

    public function generateArchive(string $saveDir)
    {
        if (empty($this->podYardConfig)) {
            throw new \Exception(
                'Empty PodYard config file'
            );
        }

        $this->dockerComposeConfig->addNetwork(
            $this->podYardConfig->getProjectName(),
            YamlTag::nilValue()
        );

        foreach ($this->podYardConfig->getServices() as $podYardServiceConfig) {
            if (empty($podYardServiceConfig->getServiceType())) {
                continue;
            }

            if (empty($this->handlers[$podYardServiceConfig->getServiceType()])) {
                continue;
            }

            $handler = clone $this->handlers[$podYardServiceConfig->getServiceType()];

            $this->configureService(
                $handler,
                $podYardServiceConfig,
                $saveDir
            );
        }
    }

    protected function configureService(
        HandlerInterface $handler,
        Entity\PodYardServiceConfig $podYardServiceConfig,
        string $saveDir
    ) {
        $handler->setPodYardServiceConfig($podYardServiceConfig)
            ->setTargetDirBase($saveDir)
            ->setProjectName($this->podYardConfig->getProjectName());

        $this->dockerComposeConfig->addService(
            $podYardServiceConfig->getName(),
            $handler->getDockerConfig()
        );

        $handler->writeFiles();

        foreach ($handler->getExtraServiceConfigs() as $extraServiceConfig) {
            $privateHandler =
                clone $this->privateHandlers[$extraServiceConfig->getServiceType()];

            $this->configureService($privateHandler, $extraServiceConfig, $saveDir);
        }
    }

    public function getDockerConfigYaml() : string
    {
        return $this->dockerComposeConfig->toYaml();
    }
}
