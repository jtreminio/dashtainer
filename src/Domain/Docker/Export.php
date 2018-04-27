<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Util;

use Symfony\Component\Yaml\Yaml;
use ZipStream\ZipStream;

class Export
{
    /** @var ZipStream */
    protected $archive;

    protected $baseDir = 'dashtainer/';

    public function setArchiver(ZipStream $archive)
    {
        $this->archive = $archive;
    }

    public function generateArchive(Entity\Docker\Project $project, bool $traefik)
    {
        if ($traefik) {
            $this->archive->addFileFromPath(
                "{$this->baseDir}traefik/docker-compose.yml",
                __DIR__ . '/../../../assets/files/traefik.yml'
            );

            $this->archive->addFileFromPath(
                "{$this->baseDir}/traefik/.env",
                __DIR__ . '/../../../assets/files/traefik.env'
            );

            $this->archive->addFileFromPath(
                "{$this->baseDir}README.md",
                __DIR__ . '/../../../assets/files/README-traefik.md'
            );

            $this->baseDir .= 'project/';
        } else {
            $this->archive->addFileFromPath(
                "{$this->baseDir}README.md",
                __DIR__ . '/../../../assets/files/README-no-traefik.md'
            );
        }

        $config = $this->getProject($project);

        $networks = $this->getNetworks($project->getNetworks());
        $config['networks'] = empty($networks) ? Util\YamlTag::emptyHash() : $networks;

        // secrets

        $volumes = $this->getVolumes($project->getVolumes());
        $config['volumes'] = empty($volumes) ? Util\YamlTag::emptyHash() : $volumes;

        $services = $this->getServices($project->getServices());
        $config['services'] = empty($services) ? Util\YamlTag::emptyHash() : $services;

        $yaml = Yaml::dump($config, 999, 2);
        $yaml = Util\YamlTag::parse($yaml);

        $this->writeYamlFile($yaml);
        $this->writeServiceFiles($project->getServices());
    }

    protected function writeYamlFile(string $yaml)
    {
        $this->archive->addFile("{$this->baseDir}docker-compose.yml", $yaml);
    }

    /**
     * @param Entity\Docker\Service[]|iterable $services
     */
    protected function writeServiceFiles(iterable $services)
    {
        foreach ($services as $service) {
            foreach ($service->getVolumes() as $volume) {
                if ($volume->getFiletype() !== Entity\Docker\ServiceVolume::FILETYPE_FILE) {
                    continue;
                }

                $filename = $service->getSlug() . '/' . $volume->getName();
                $this->archive->addFile("{$this->baseDir}{$filename}", $volume->getData());
            }
        }
    }

    protected function getProject(Entity\Docker\Project $project)
    {
        $config = [
            'version' => '3.2',
        ];

        $environments = [];
        foreach ($project->getEnvironments() as $k => $v) {
            if (empty($v)) {
                $environments []= $k;

                continue;
            }

            $environments []= "{$k}={$v}";
        }

        if (!empty($environments)) {
            $this->archive->addFile("{$this->baseDir}.env", implode("\n", $environments));
        }

        return $config;
    }

    /**
     * @param Entity\Docker\Network[]|iterable $networks
     * @return array
     */
    protected function getNetworks(iterable $networks) : array
    {
        $arr = [];

        foreach ($networks as $network) {
            $current = [];

            if (!empty($network->getDriver())) {
                $current['driver'] = $network->getDriver();
            }

            if ($network->getExternal() === true) {
                $current['external'] = true;
            } elseif ($network->getExternal()) {
                $current['external']['name'] = $network->getExternal();
            }

            foreach ($network->getLabels() as $k => $v) {
                $sub['labels'] []= "{$k}={$v}";
            }

            $arr[$network->getName()] = empty($current) ? Util\YamlTag::nilValue() : $current;
        }

        return $arr;
    }

    /**
     * @param Entity\Docker\Volume[]|iterable $volumes
     * @return array
     */
    protected function getVolumes(iterable $volumes) : array
    {
        $arr = [];

        foreach ($volumes as $volume) {
            $current = [];

            if (!empty($volume->getDriver())) {
                $current['driver'] = $volume->getDriver();
            }

            foreach ($volume->getDriverOpts() as $k => $v) {
                $current['driver_opts'][$k] = $v;
            }

            if ($volume->getExternal() === true) {
                $current['external'] = true;
            } elseif ($volume->getExternal()) {
                $current['external']['name'] = $volume->getExternal();
            }

            foreach ($volume->getLabels() as $k => $v) {
                $sub['labels'] []= "{$k}={$v}";
            }

            $arr[$volume->getSlug()] = empty($current) ? Util\YamlTag::nilValue() : $current;
        }

        return $arr;
    }

    /**
     * @param Entity\Docker\Service[]|iterable $services
     * @return array
     */
    protected function getServices(iterable $services) : array
    {
        $arr = [];

        foreach ($services as $service) {
            $current = [];

            if (!empty($service->getImage())) {
                $current['image'] = $service->getImage();
            }

            if ($build = $service->getBuild()) {
                $sub = [];

                if ($context = $build->getContext()) {
                    $sub['context'] = $context;
                }

                if ($dockerfile = $build->getDockerfile()) {
                    $sub['dockerfile'] = $dockerfile;
                }

                foreach ($build->getArgs() as $k => $v) {
                    $v = is_array($v) ? implode(' ', $v) : $v;

                    $sub['args'][$k]= $v;
                }

                if (!empty($build->getCacheFrom())) {
                    $sub['cache_from'] []= $build->getCacheFrom();
                }

                foreach ($build->getLabels() as $k => $v) {
                    $sub['labels'] []= "{$k}={$v}";
                }

                if ($shmSize = $build->getShmSize()) {
                    $sub['shm_size'] = $shmSize;
                }

                if ($target = $build->getTarget()) {
                    $sub['target'] = $target;
                }

                if (!empty($sub)) {
                    $current['build'] = $sub;
                }
            }

            if ($command = $service->getCommand()) {
                $current['command'] = $command;
            }

            if ($deploy = $service->getDeploy()) {
                $sub = [
                    'mode' => $deploy->getMode(),
                ];

                foreach ($deploy->getLabels() as $k => $v) {
                    $sub['labels'] []= "{$k}={$v}";
                }

                $placement = $deploy->getPlacement();
                if (!empty($placement->getConstraints())) {
                    $sub['placement']['constraints'] = $placement->getConstraints();
                }

                if (!empty($placement->getPreferences())) {
                    $sub['placement']['preferences'] = $placement->getPreferences();
                }

                if ($deploy->getMode() == 'replicated' && $deploy->getReplicas()) {
                    $sub['replicas'] = $deploy->getReplicas();
                }

                $resources = $deploy->getResources();
                if (!empty($resources->getLimits())) {
                    $sub['resources']['limits'] = $resources->getLimits();
                }

                if (!empty($resources->getReservations())) {
                    $sub['resources']['reservations'] = $resources->getReservations();
                }

                $restartPolicy = $deploy->getRestartPolicy();
                $sub['restart_policy']['condition'] = $restartPolicy->getCondition();
                $sub['restart_policy']['delay'] = $restartPolicy->getDelay();

                if ($maxAttempts = $restartPolicy->getMaxAttempts()) {
                    $sub['restart_policy']['max_attempts'] = $maxAttempts;
                }

                $sub['restart_policy']['window'] = $restartPolicy->getWindow();

                $current['deploy'] = $sub;
            }

            foreach ($service->getDevices() as $k => $v) {
                $current['devices'] []= "{$k}:{$v}";
            }

            if (!empty($service->getDependsOn())) {
                $current['depends_on'] = $service->getDependsOn();
            }

            if (!empty($service->getDns())) {
                $current['dns'] = $service->getDns();
            }

            if (!empty($service->getDnsSearch())) {
                $current['dns_search'] = $service->getDnsSearch();
            }

            if (!empty($service->getEntrypoint())) {
                $current['entrypoint'] = $service->getEntrypoint();
            }

            if (!empty($service->getEnvFile())) {
                $current['env_file'] = $service->getEnvFile();
            }

            foreach ($service->getEnvironments() as $k => $v) {
                if (empty($v)) {
                    $current['environment'] []= $k;

                    continue;
                }

                $current['environment'] []= "{$k}={$v}";
            }

            if (!empty($service->getExpose())) {
                $current['expose'] = $service->getExpose();
            }

            if (!empty($service->getExtraHosts())) {
                $current['extra_hosts'] = $service->getExtraHosts();
            }

            $healthcheck = $service->getHealthcheck();
            if (!empty($healthcheck->getTest())) {
                $current['healthcheck'] = [
                    'test'     => $healthcheck->getTest(),
                    'interval' => $healthcheck->getInterval(),
                    'timeout'  => $healthcheck->getTimeout(),
                    'retries'  => $healthcheck->getRetries(),
                ];
            }

            if (!empty($service->getIsolation())) {
                $current['isolation'] = $service->getIsolation();
            }

            foreach ($service->getLabels() as $k => $v) {
                $current['labels'] []= "{$k}={$v}";
            }

            if ($logging = $service->getLogging()) {
                $current['logging']['driver'] = $logging->getDriver();

                foreach ($logging->getOptions() as $k => $v) {
                    $current['logging'][$k] = $v;
                }
            }

            if (!empty($service->getNetworkMode())) {
                $current['network_mode'] = $service->getNetworkMode();
            }

            foreach ($service->getNetworks() as $network) {
                $current['networks'] []= $network->getName();
            }

            if (!empty($service->getPid())) {
                $current['pid'] = $service->getPid();
            }

            if (!empty($service->getPorts())) {
                $current['ports'] = $service->getPorts();
            }

            if (!empty($service->getRestart())) {
                $current['restart'] = $service->getRestart();
            }

            foreach ($service->getSecrets() as $secret) {
                $current['secrets'] []= $secret->getSlug();
            }

            if (!empty($service->getStopGracePeriod())) {
                $current['stop_grace_period'] = $service->getStopGracePeriod();
            }

            if (!empty($service->getStopSignal())) {
                $current['stop_signal'] = $service->getStopSignal();
            }

            foreach ($service->getSysctls() as $k => $v) {
                $current['sysctls'] []= "{$k}={$v}";
            }

            $ulimits = $service->getUlimits();
            if ($ulimits->getNproc()) {
                $current['ulimits'] = [
                    'nproc' => $ulimits->getNproc(),
                ];

                if (!empty($ulimits->getNofile())) {
                    $current['ulimits']['nofile'] = $ulimits->getNofile();
                }
            }

            if ($ulimits->getMemlock()) {
                $current['ulimits']['memlock'] = $ulimits->getMemlock();
            }

            if (!empty($service->getUsernsMode())) {
                $current['userns_mode'] = $service->getUsernsMode();
            }

            foreach ($service->getVolumes() as $volume) {
                if (empty($volume->getTarget())) {
                    continue;
                }

                $string = "{$volume->getSource()}:{$volume->getTarget()}";
                $string = $volume->getConsistency()
                    ? "{$string}:{$volume->getConsistency()}"
                    : $string;

                $current['volumes'] []= Util\YamlTag::doubleQuotes($string);
            }

            $arr[$service->getSlug()] = $current;
        }

        return $arr;
    }
}
