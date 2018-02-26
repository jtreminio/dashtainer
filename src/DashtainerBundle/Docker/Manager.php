<?php

namespace DashtainerBundle\Docker;

use DashtainerBundle\Entity;
use DashtainerBundle\Util\YamlTag;

class Manager
{
    /** @var Entity\DockerComposeConfig */
    protected $dockerComposeConfig;

    /** @var HandlerInterface[] */
    protected $handlers = [];

    /** @var Entity\DashtainerConfig */
    protected $dashtainerConfig;

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
    public function setDashtainerConfig(array $config)
    {
        $this->dashtainerConfig = new Entity\DashtainerConfig($config);

        return $this;
    }

    public function generateArchive(string $saveDir)
    {
        if (empty($this->dashtainerConfig)) {
            throw new \Exception(
                'Empty Dashtainer config file'
            );
        }

        $this->dockerComposeConfig->addNetwork(
            $this->dashtainerConfig->getProjectName(),
            YamlTag::nilValue()
        );

        foreach ($this->dashtainerConfig->getServices() as $dashtainerServiceConfig) {
            if (empty($dashtainerServiceConfig->getServiceType())) {
                continue;
            }

            if (empty($this->handlers[$dashtainerServiceConfig->getServiceType()])) {
                continue;
            }

            $handler = clone $this->handlers[$dashtainerServiceConfig->getServiceType()];

            $this->configureService(
                $handler,
                $dashtainerServiceConfig,
                $saveDir
            );
        }
    }

    protected function configureService(
        HandlerInterface $handler,
        Entity\DashtainerServiceConfig $dashtainerServiceConfig,
        string $saveDir
    ) {
        $handler->setDashtainerServiceConfig($dashtainerServiceConfig)
            ->setTargetDirBase($saveDir)
            ->setProjectName($this->dashtainerConfig->getProjectName());

        $this->dockerComposeConfig->addService(
            $dashtainerServiceConfig->getName(),
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
