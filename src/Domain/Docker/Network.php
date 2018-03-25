<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class Network
{
    /** @var Repository\Docker\Network */
    protected $repo;

    /** @var Repository\Docker\Service */
    protected $serviceRepo;

    protected $wordsListFile;

    public function __construct(
        Repository\Docker\Network $repo,
        Repository\Docker\Service $serviceRepo,
        string $wordListFile
    ) {
        $this->repo = $repo;

        $this->serviceRepo   = $serviceRepo;
        $this->wordsListFile = $wordListFile;
    }

    public function createFromForm(
        Form\Docker\NetworkCreateUpdate $form
    ) : Entity\Docker\Network {
        $services = $this->serviceRepo->findBy([
            'project' => $form->project,
            'name'    => $form->services,
        ]);

        $network = new Entity\Docker\Network();
        $network->setName($form->name)
            ->setIsRemovable(true)
            ->setProject($form->project);

        foreach ($services as $service) {
            $network->addService($service);
            $service->addNetwork($network);
        }

        $this->repo->save($network, ...$services);

        return $network;
    }

    public function updateFromForm(
        Form\Docker\NetworkCreateUpdate $form
    ) : Entity\Docker\Network {
        $network = $this->repo->findOneBy(['name' => $form->name]);

        $nonSelectedServices = [];
        $selectedServices    = [];

        foreach ($this->serviceRepo->findAllByProject($form->project) as $service) {
            if (!in_array($service->getName(), $form->services)) {
                $network->removeService($service);
                $service->removeNetwork($network);

                $nonSelectedServices []= $service;

                continue;
            }

            $network->addService($service);
            $service->addNetwork($network);

            $selectedServices []= $service;
        }

        $this->repo->save($network, ...$nonSelectedServices, ...$selectedServices);

        return $network;
    }

    public function delete(Entity\Docker\Network $network)
    {
        $services = [];
        foreach ($network->getServices() as $service) {
            $network->removeService($service);
            $service->removeNetwork($network);

            $services []= $service;
        }

        $this->repo->save($network, ...$services);
        $this->repo->delete($network);
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
