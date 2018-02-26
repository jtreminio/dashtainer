<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util\YamlTag;

use Symfony\Component\Yaml\Yaml;

class DockerComposeConfig
{
    protected $config = [
        'version'  => '3.1',
        'networks' => [
            'web' => [
                'external' => [
                    'name' => 'traefik_webgateway'
                ],
            ],
        ],
        'services' => [],
    ];

    /**
     * @param string       $networkName
     * @param string|array $value
     * @return $this
     */
    public function addNetwork(string $networkName, $value)
    {
        $this->config['networks'][$networkName] = $value;

        return $this;
    }

    public function addService(string $serviceName, $service)
    {
        $this->config['services'][$serviceName] = $service;

        return $this;
    }

    public function toYaml() : string
    {
        $yaml = Yaml::dump($this->config, 999, 2);

        return YamlTag::parse($yaml);
    }
}
