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
            if ($network->getExternal()) {
                $arr[$network->getName()] = [
                    'external' => [
                        'name' => $network->getExternal(),
                    ],
                ];

                continue;
            }

            $arr[$network->getName()] = Util\YamlTag::nilValue();
        }

        return $arr;
    }
}
