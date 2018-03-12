<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class DockerNetwork
{
    /** @var Repository\DockerNetworkRepository */
    protected $repo;

    public function __construct(Repository\DockerNetworkRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createNetworkFromForm(
        Form\DockerNetworkCreateUpdateForm $form
    ) : Entity\DockerNetwork {
        $network = new Entity\DockerNetwork();
        $network->fromArray($form->toArray());

        $this->repo->save($network);

        return $network;
    }

    /**
     * @param Entity\DockerNetwork[]|iterable $networks
     * @return array
     */
    public function export(iterable $networks) : array
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
}
