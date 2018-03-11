<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

use Symfony\Component\Yaml\Yaml;

class Export
{
    /** @var DockerNetwork */
    protected $networkDomain;

    /** @var DockerProject */
    protected $projectDomain;

    /** @var DockerService */
    protected $serviceDomain;

    public function __construct(
        DockerProject $projectDomain,
        DockerNetwork $networkDomain,
        DockerService $serviceDomain
    ) {
        $this->projectDomain = $projectDomain;
        $this->networkDomain = $networkDomain;
        $this->serviceDomain = $serviceDomain;
    }

    public function export(Entity\DockerProject $project)
    {
        $config = [
            'version'  => '3.1',
        ];

        $networks = $this->networkDomain->export($project->getNetworks());

        $config['networks'] = empty($networks) ? Util\YamlTag::emptyHash() : $networks;

        // secrets

        // volumes

        $services = $this->serviceDomain->export($project->getServices());

        $config['services'] = empty($services) ? Util\YamlTag::emptyHash() : $services;

        $yaml = Yaml::dump($config, 999, 2);

        return Util\YamlTag::parse($yaml);
    }
}
