<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Entity\User;
use Dashtainer\Form\Docker as Form;
use Dashtainer\Repository\Docker as Repository;

class Project
{
    /** @var Repository\Project */
    protected $repo;

    public function __construct(Repository\Project $repo)
    {
        $this->repo = $repo;
    }

    public function create(Form\ProjectCreateUpdate $form) : Entity\Project
    {
        $project = new Entity\Project();
        $project->fromArray($form->toArray());

        $public = new Entity\Network();
        $public->setName('public')
            ->setIsEditable(false)
            ->setIsPublic(true)
            ->setExternal('traefik_webgateway');

        $private = new Entity\Network();
        $private->setName('private')
            ->setIsEditable(false);

        $project->setUser($form->user)
            ->addNetwork($public)
            ->addNetwork($private);

        $this->repo->persist($public, $private, $project);
        $this->repo->flush();

        return $project;
    }

    public function update(Entity\Project $project)
    {
        $this->repo->persist($project);
        $this->repo->flush();
    }

    public function getByUserAndId(User $user, string $projectId) : ?Entity\Project
    {
        return $this->repo->findByUserAndId($user, $projectId);
    }

    public function getByUserAndName(User $user, string $projectName) : ?Entity\Project
    {
        return $this->repo->findByUserAndName($user, $projectName);
    }

    /**
     * @param User $user
     * @return array [id, name, service_count]
     */
    public function getList(User $user) : array
    {
        return $this->repo->getNamesAndCount($user);
    }

    public function delete(Entity\Project $project)
    {
        foreach ($project->getServices() as $service) {
            $this->deleteService($service);
            $project->removeService($service);
            $this->repo->remove($service);
        }

        foreach ($project->getNetworks() as $network) {
            $project->removeNetwork($network);
            $this->repo->remove($network);
        }

        foreach ($project->getSecrets() as $projectSecret) {
            $project->removeSecret($projectSecret);
            $this->repo->remove($projectSecret);
        }

        foreach ($project->getVolumes() as $projectVolume) {
            $project->removeVolume($projectVolume);
            $this->repo->remove($projectVolume);
        }

        $this->repo->remove($project);
        $this->repo->flush();
    }

    protected function deleteService(Entity\Service $service)
    {
        foreach ($service->getChildren() as $child) {
            $this->deleteService($child);

            $service->removeChild($child);
            $this->repo->remove($child);
        }

        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);
            $this->repo->remove($meta);
        }

        // Do not remove Network here, done later
        foreach ($service->getNetworks() as $network) {
            $service->removeNetwork($network);
        }

        foreach ($service->getPorts() as $port) {
            $service->removePort($port);
            $this->repo->remove($port);
        }

        foreach ($service->getSecrets() as $serviceSecret) {
            if ($projectSecret = $serviceSecret->getProjectSecret()) {
                $projectSecret->removeServiceSecret($serviceSecret);
            }
            $service->removeSecret($serviceSecret);
            $this->repo->remove($serviceSecret);
        }

        foreach ($service->getVolumes() as $serviceVolume) {
            if ($projectVolume = $serviceVolume->getProjectVolume()) {
                $projectVolume->removeServiceVolume($serviceVolume);
            }
            $service->removeVolume($serviceVolume);
            $this->repo->remove($serviceVolume);
        }
    }
}
