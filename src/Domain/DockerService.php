<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerService
{
    /** @var Repository\DockerServiceRepository */
    protected $repo;

    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    /** @var ServiceHandlerStore */
    protected $serviceHandler;

    public function __construct(
        Repository\DockerServiceRepository $repo,
        Repository\DockerNetworkRepository $networkRepo,
        ServiceHandlerStore $serviceHandler
    ) {
        $this->repo           = $repo;
        $this->networkRepo    = $networkRepo;
        $this->serviceHandler = $serviceHandler;
    }

    public function createService(
        Form\Service\CreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->create($form);
    }

    public function deleteService(Entity\DockerService $service)
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        $handler->delete($service);
    }

    public function updateService(
        Entity\DockerService $service,
        Form\Service\CreateAbstract $form
    ) : Entity\DockerService {
        $handler = $this->serviceHandler->getHandlerFromForm($form);

        return $handler->update($service, $form);
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType
    ) : Form\Service\CreateAbstract {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateForm($serviceType);
    }

    public function getCreateParams(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType
    ) : array {
        $handler = $this->serviceHandler->getHandlerFromType($serviceType);

        return $handler->getCreateParams($project);
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        $handler = $this->serviceHandler->getHandlerFromType($service->getType());

        return $handler->getViewParams($service);
    }

    public function generateServiceName(
        Entity\DockerProject $project,
        Entity\DockerServiceType $serviceType,
        string $version = null
    ) : string {
        $services = $this->repo->findBy([
            'project' => $project,
            'type'    => $serviceType,
        ]);

        $version  = $version ? "-{$version}" : '';
        $hostname = Util\Strings::hostname("{$serviceType->getSlug()}{$version}");

        if (empty($services)) {
            return $hostname;
        }

        $usedNames = [];
        foreach ($services as $service) {
            $usedNames []= $service->getName();
        }

        for ($i = 1; $i <= count($usedNames); $i++) {
            $name = "{$hostname}-{$i}";

            if (!in_array($name, $usedNames)) {
                return $name;
            }
        }

        return "{$hostname}-" . uniqid();
    }

    /**
     * @param Entity\DockerService[]|iterable $services
     * @return array
     */
    public function export(iterable $services) : array
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

            $logging = $service->getLogging();
            $current['logging']['driver'] = $logging->getDriver();

            foreach ($logging->getOptions() as $k => $v) {
                $current['logging'][$k] = $v;
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

            $current['restart'] = $service->getRestart();

            foreach ($service->getSecrets() as $secret) {
                $current['secrets'] []= $secret->getSlug();
            }

            $current['stop_grace_period'] = $service->getStopGracePeriod();

            $current['stop_signal'] = $service->getStopSignal();

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

            if (!empty($service->getUsernsMode())) {
                $current['userns_mode'] = $service->getUsernsMode();
            }

            foreach ($service->getVolumes() as $volume) {
                $sub = [
                    'type'        => 'bind',
                    'source'      => Util\YamlTag::doubleQuotes($volume->getSource()),
                    'target'      => Util\YamlTag::doubleQuotes($volume->getTarget()),
                    'propagation' => $volume->getConsistency(),
                ];

                $current['volumes'] []= $sub;
            }

            $arr[$service->getSlug()] = $current;
        }

        return $arr;
    }
}
