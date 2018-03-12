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

    /** @var DockerVolume */
    protected $volumeDomain;

    public function __construct(
        DockerNetwork $networkDomain,
        DockerProject $projectDomain,
        DockerService $serviceDomain,
        DockerVolume $volumeDomain
    ) {
        $this->networkDomain = $networkDomain;
        $this->projectDomain = $projectDomain;
        $this->serviceDomain = $serviceDomain;
        $this->volumeDomain  = $volumeDomain;
    }

    public function export(Entity\DockerProject $project)
    {
        $config = [
            'version'  => '3.1',
        ];

        $networks = $this->networkDomain->export($project->getNetworks());
        $config['networks'] = empty($networks) ? Util\YamlTag::emptyHash() : $networks;

        // secrets

        $volumes = $this->volumeDomain->export($project->getVolumes());
        $config['volumes'] = empty($volumes) ? Util\YamlTag::emptyHash() : $volumes;

        $services = $this->serviceDomain->export($project->getServices());
        $config['services'] = empty($services) ? Util\YamlTag::emptyHash() : $services;

        $yaml = Yaml::dump($config, 999, 2);

        return Util\YamlTag::parse($yaml);
    }
}
