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

    protected $baseDir = 'dashtainer';

    /** @var Entity\Docker\Project */
    protected $project;

    public function setArchiver(ZipStream $archive)
    {
        $this->archive = $archive;
    }

    public function dump(Entity\Docker\Project $project) : string
    {
        $this->project = $project;

        return $this->generate();
    }

    public function download(Entity\Docker\Project $project, bool $traefik)
    {
        $this->project = $project;

        if ($traefik) {
            $this->addFileFromPath(
                "{$this->baseDir}/traefik/docker-compose.yml",
                __DIR__ . '/../../../assets/files/traefik.yml'
            );

            $this->addFileFromPath(
                "{$this->baseDir}/traefik/.env",
                __DIR__ . '/../../../assets/files/traefik.env'
            );

            $this->addFileFromPath(
                "{$this->baseDir}/README.md",
                __DIR__ . '/../../../assets/files/README-traefik.md'
            );

            $this->baseDir .= '/project';
        } else {
            $this->addFileFromPath(
                "{$this->baseDir}/README.md",
                __DIR__ . '/../../../assets/files/README-no-traefik.md'
            );
        }

        $yaml = $this->generate();

        $this->addFile("{$this->baseDir}/docker-compose.yml", $yaml);
    }

    protected function generate() : string
    {
        $config = $this->getProject();

        $networks = $this->getNetworks($this->project->getNetworks());
        $config['networks'] = empty($networks) ? Util\YamlTag::emptyHash() : $networks;

        $secrets = $this->getSecrets($this->project->getSecrets());
        $config['secrets'] = empty($secrets) ? Util\YamlTag::emptyHash() : $secrets;

        $volumes = $this->getVolumes($this->project->getVolumes());
        $config['volumes'] = empty($volumes) ? Util\YamlTag::emptyHash() : $volumes;

        $services = $this->getServices($this->project->getServices());
        $config['services'] = empty($services) ? Util\YamlTag::emptyHash() : $services;

        $yaml = Yaml::dump($config, 999, 2);

        return Util\YamlTag::parse($yaml);
    }

    protected function addFile(string $target, string $data = null)
    {
        if (!$this->archive) {
            return;
        }

        $this->archive->addFile($target, $data);
    }

    protected function addFileFromPath(string $source, string $target)
    {
        if (!$this->archive) {
            return;
        }

        $this->archive->addFileFromPath($source, $target);
    }

    protected function getProject() : array
    {
        $environments = [
            "COMPOSE_PROJECT_NAME={$this->project->getSlug()}",
        ];

        foreach ($this->project->getEnvironments() as $k => $v) {
            if (empty($v)) {
                $environments []= $k;

                continue;
            }

            $environments []= "{$k}={$v}";
        }

        $this->addFile("{$this->baseDir}/.env", implode("\n", $environments));

        return [
            'version' => '3.2',
        ];
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
     * @param Entity\Docker\Secret[]|iterable $secrets
     * @return array
     */
    protected function getSecrets(iterable $secrets) : array
    {
        $arr = [];

        foreach ($secrets as $secret) {
            $arr [$secret->getSlug()]= [
                'file' => $secret->getFile(),
            ];

            $filename = ltrim($secret->getFile(), '.');
            $filename = ltrim($filename, '/');

            $this->addFile("{$this->baseDir}/{$filename}", $secret->getData());
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

            if ($build = $this->serviceBuild($service)) {
                $current['build'] = $build;
            }

            if ($command = $service->getCommand()) {
                $current['command'] = $command;
            }

            if ($deploy = $this->serviceDeploy($service)) {
                $current['deploy'] = $deploy;
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
                $current['secrets'] []= $secret->getProjectSecret()->getSlug();
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

            if ($volumes = $this->serviceVolumes($service)) {
                $current['volumes'] = $volumes;
            }

            $arr[$service->getSlug()] = $current;
        }

        return $arr;
    }

    protected function serviceBuild(Entity\Docker\Service $service) : ?array
    {
        $build = $service->getBuild();

        if (empty($build)) {
            return null;
        }

        $arr = [];

        if ($context = $build->getContext()) {
            $arr['context'] = $context;
        }

        if ($dockerfile = $build->getDockerfile()) {
            $arr['dockerfile'] = $dockerfile;
        }

        foreach ($build->getArgs() as $k => $v) {
            $v = is_array($v) ? implode(' ', $v) : $v;

            $arr['args'][$k]= $v;
        }

        if (!empty($build->getCacheFrom())) {
            $arr['cache_from'] []= $build->getCacheFrom();
        }

        foreach ($build->getLabels() as $k => $v) {
            $arr['labels'] []= "{$k}={$v}";
        }

        if ($shmSize = $build->getShmSize()) {
            $arr['shm_size'] = $shmSize;
        }

        if ($target = $build->getTarget()) {
            $arr['target'] = $target;
        }

        return !empty($arr) ? $arr : null;
    }

    protected function serviceDeploy(Entity\Docker\Service $service) : ?array
    {
        $deploy = $service->getDeploy();

        if (empty($deploy)) {
            return null;
        }

        $arr = [
            'mode' => $deploy->getMode(),
        ];

        foreach ($deploy->getLabels() as $k => $v) {
            $arr['labels'] []= "{$k}={$v}";
        }

        $placement = $deploy->getPlacement();
        if (!empty($placement->getConstraints())) {
            $arr['placement']['constraints'] = $placement->getConstraints();
        }

        if (!empty($placement->getPreferences())) {
            $arr['placement']['preferences'] = $placement->getPreferences();
        }

        if ($deploy->getMode() == 'replicated' && $deploy->getReplicas()) {
            $arr['replicas'] = $deploy->getReplicas();
        }

        $resources = $deploy->getResources();
        if (!empty($resources->getLimits())) {
            $arr['resources']['limits'] = $resources->getLimits();
        }

        if (!empty($resources->getReservations())) {
            $arr['resources']['reservations'] = $resources->getReservations();
        }

        $restartPolicy = $deploy->getRestartPolicy();
        $arr['restart_policy']['condition'] = $restartPolicy->getCondition();
        $arr['restart_policy']['delay'] = $restartPolicy->getDelay();

        if ($maxAttempts = $restartPolicy->getMaxAttempts()) {
            $arr['restart_policy']['max_attempts'] = $maxAttempts;
        }

        $arr['restart_policy']['window'] = $restartPolicy->getWindow();

        return $arr;
    }

    protected function serviceVolumes(Entity\Docker\Service $service) : ?array
    {
        $arr = [];

        foreach ($service->getVolumes() as $serviceVolume) {
            $consistency = $serviceVolume->getConsistency()
                ? ":{$serviceVolume->getConsistency()}"
                : '';

            // file with data
            if ($serviceVolume->getFiletype() == Entity\Docker\ServiceVolume::FILETYPE_FILE) {
                // file with source & target
                if ($serviceVolume->getSource() && $serviceVolume->getTarget()) {
                    $filename = $service->getSlug() . '/' . $serviceVolume->getSource();

                    $string = "./{$filename}:{$serviceVolume->getTarget()}{$consistency}";
                    $arr []= Util\YamlTag::doubleQuotes($string);

                    $this->addFile("{$this->baseDir}/{$filename}", $serviceVolume->getData());

                    continue;
                }

                // file with source, no target
                if ($serviceVolume->getSource() && empty($serviceVolume->getTarget())) {
                    $filename = $service->getSlug() . '/' . $serviceVolume->getSource();

                    $this->addFile("{$this->baseDir}/{$filename}", $serviceVolume->getData());

                    continue;
                }
            }

            // Non-File Volume
            if ($serviceVolume->getFiletype() == Entity\Docker\ServiceVolume::FILETYPE_OTHER) {
                // local
                if ($serviceVolume->getType() == Entity\Docker\ServiceVolume::TYPE_BIND) {
                    $filenamePrepend = $serviceVolume->getPrepend()
                        ? $service->getSlug() . '/'
                        : '';

                    $filename = $filenamePrepend . $serviceVolume->getSource();

                    $string = "{$filename}:{$serviceVolume->getTarget()}{$consistency}";
                    $arr []= Util\YamlTag::doubleQuotes($string);

                    continue;
                }

                // docker volume
                if ($serviceVolume->getType() == Entity\Docker\ServiceVolume::TYPE_VOLUME) {
                    if (!$projectVolume = $serviceVolume->getProjectVolume()) {
                        continue;
                    }

                    $string = "{$projectVolume->getSlug()}:{$serviceVolume->getTarget()}";
                    $arr []= Util\YamlTag::doubleQuotes($string);

                    continue;
                }
            }
        }

        return !empty($arr) ? $arr : null;
    }
}
