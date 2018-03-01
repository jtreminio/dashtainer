<?php

namespace Dashtainer\Entity;

class DashtainerServiceConfig
{
    protected $service_type;

    protected $name;

    protected $environment = [];

    protected $labels = [];

    protected $networks = [];

    protected $settings = [];

    protected $volumes = [];

    public function __construct(array $config)
    {
        $this->setServiceType($config['service_type'])
            ->setName($config['name'])
            ->setEnvironment($config['environment'] ?? [])
            ->setLabels($config['labels'] ?? [])
            ->setNetworks($config['networks'] ?? [])
            ->setSettings($config['settings'] ?? [])
            ->setVolumes($config['volumes'] ?? []);
    }

    /**
     * @return string
     */
    public function getServiceType() : string
    {
        return $this->service_type;
    }

    /**
     * @param string $service_type
     * @return $this
     */
    public function setServiceType(string $service_type)
    {
        $this->service_type = $service_type;

        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
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

    /**
     * @return array
     */
    public function getEnvironment() : array
    {
        return $this->environment;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addEnvironment(string $key, string $value)
    {
        $this->environment[$key] = $value;

        return $this;
    }

    /**
     * @param array $environment
     * @return $this
     */
    public function setEnvironment(array $environment)
    {
        foreach ($environment as $key => $value) {
            $this->environment[$key] = $value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLabels() : array
    {
        return $this->labels;
    }

    /**
     * @param array $labels
     * @return $this
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function getSettings() : array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return array
     */
    public function getVolumes() : array
    {
        return $this->volumes;
    }

    /**
     * @param array $volumes
     * @return $this
     */
    public function setVolumes(array $volumes)
    {
        $this->volumes = $volumes;

        return $this;
    }
}
