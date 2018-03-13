<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Network
{
    /** @var Repository\Docker\Network */
    protected $repo;

    public function __construct(Repository\Docker\Network $repo)
    {
        $this->repo = $repo;
    }

    public function createNetworkFromForm(
        Form\Docker\NetworkCreateUpdate $form
    ) : Entity\Docker\Network {
        $network = new Entity\Docker\Network();
        $network->fromArray($form->toArray());

        $this->repo->save($network);

        return $network;
    }
}
