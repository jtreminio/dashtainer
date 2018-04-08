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

    /**
     * Creates a new Network.
     *
     * Associates network with Project and Services.
     *
     * @param Form\Docker\NetworkCreateUpdate $form
     * @return Entity\Docker\Network
     */
    public function createFromForm(
        Form\Docker\NetworkCreateUpdate $form
    ) : Entity\Docker\Network {
        $services = $this->serviceRepo->findBy([
            'project' => $form->project,
            'name'    => $form->services,
        ]);

        $network = new Entity\Docker\Network();
        $network->setName($form->name)
            ->setIsEditable(true)
            ->setProject($form->project);

        foreach ($services as $service) {
            $network->addService($service);
            $service->addNetwork($network);
        }

        $this->repo->save($network, ...$services);

        return $network;
    }

    /**
     * Updates an existing Network.
     *
     * Cycles through all Services in Project and removes or adds
     * Network associations.
     *
     * Cycling through all Services makes it easier to map
     * the associations.
     *
     * @param Form\Docker\NetworkCreateUpdate $form
     * @return Entity\Docker\Network
     */
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

    /**
     * Deletes Network from Project.
     *
     * Removes association between Services and Network.
     *
     * @param Entity\Docker\Network $network
     */
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

    /**
     * Picks random string from word list file for a Network name.
     *
     * Does a diff between existing Network names and possible results.
     *
     * @param Entity\Docker\Project $project
     * @return string
     */
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
}
