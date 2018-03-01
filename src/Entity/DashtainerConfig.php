<?php

namespace Dashtainer\Entity;

class DashtainerConfig
{
    protected const CURRENT_VERSION = 1.0;

    protected $project_name;

    protected $services = [];

    protected $version;

    public function __construct(array $config)
    {
        $this->setProjectName($config['project_name'] ?? 'dashtainer')
            ->setVersion($config['version'] ?? static::CURRENT_VERSION)
            ->setServices($config['services'] ?? []);
    }

    /**
     * @return string
     */
    public function getProjectName() : string
    {
        return $this->project_name;
    }

    /**
     * @param string $project_name
     * @return $this
     */
    public function setProjectName(string $project_name)
    {
        $this->project_name = $project_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion() : string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = (string) number_format($version, 1);

        return $this;
    }

    /**
     * @return DashtainerServiceConfig[]
     */
    public function getServices() : array
    {
        return $this->services;
    }

    /**
     * @param array $services
     * @return $this
     */
    public function setServices(array $services)
    {
        foreach ($services as $serviceLabel => $service) {
            $this->services[$serviceLabel] = new DashtainerServiceConfig($service);
        }

        return $this;
    }
}
