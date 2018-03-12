<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerVolume
{
    /** @var Repository\DockerVolumeRepository */
    protected $repo;

    public function __construct(Repository\DockerVolumeRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param Entity\DockerVolume[]|iterable $volumes
     * @return array
     */
    public function export(iterable $volumes) : array
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
}
