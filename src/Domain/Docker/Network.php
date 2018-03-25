<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Network
{
    /** @var Repository\Docker\Network */
    protected $repo;

    protected $wordsListFile;

    public function __construct(Repository\Docker\Network $repo, string $wordListFile)
    {
        $this->repo = $repo;
        $this->wordsListFile = $wordListFile;
    }

    public function createNetworkFromForm(
        Form\Docker\NetworkCreateUpdate $form
    ) : Entity\Docker\Network {
        $network = new Entity\Docker\Network();
        $network->fromArray($form->toArray());

        $this->repo->save($network);

        return $network;
    }

    public function generateName(Entity\Docker\Project $project) : string
    {
        $existingNetworks = $this->repo->findBy([
            'project' => $project
        ]);

        $existingNames = [];
        foreach ($existingNetworks as $network) {
            $existingNames []= $network->getName();
        }

        $file = file($this->wordsListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $diff = array_diff($file, $existingNames);

        return trim($diff[array_rand($diff)]);
    }

    public function isNameUsed(Entity\Docker\Project $project, array $names) : array
    {
        $existingNetworks = $this->repo->findBy([
            'project' => $project
        ]);

        $existingNames = [];
        foreach ($existingNetworks as $network) {
            $existingNames []= $network->getName();
        }

        return array_intersect($names, $existingNames);
    }

    public function exist(Entity\Docker\Project $project, array $names) : array
    {
        $existingNetworks = $this->repo->findBy([
            'project' => $project
        ]);

        $existingNames = [];
        foreach ($existingNetworks as $network) {
            $existingNames []= $network->getName();
        }

        return array_diff($names, $existingNames);
    }
}
