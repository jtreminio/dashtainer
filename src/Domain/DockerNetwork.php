<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

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
}
