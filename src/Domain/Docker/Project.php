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

    public function createProjectFromForm(
        Form\ProjectCreateUpdate $form,
        User $user
    ) : Entity\Project {
        $project = new Entity\Project();
        $project->fromArray($form->toArray());

        $public = new Entity\Network();
        $public->setName('public')
            ->setIsEditable(false)
            ->setIsPublic(true)
            ->setExternal('traefik_webgateway')
            ->setProject($project);

        $private = new Entity\Network();
        $private->setName('private')
            ->setIsEditable(false)
            ->setProject($project);

        $project->setUser($user)
            ->addNetwork($public)
            ->addNetwork($private);

        $this->repo->save($public, $private, $project);

        return $project;
    }

    public function delete(Entity\Project $project)
    {
        $deleted = [];

        $deleteServices = function(Entity\Service $service)
            use (&$deleteServices, $project)
        {
            $deleted = [];

            foreach ($service->getMetas() as $meta) {
                $service->removeMeta($meta);

                $deleted[spl_object_hash($meta)]= $meta;
            }

            foreach ($service->getNetworks() as $network) {
                $service->removeNetwork($network);
                $network->removeService($service);

                $deleted[spl_object_hash($network)]= $network;
            }

            foreach ($service->getSecrets() as $serviceSecret) {
                $service->removeSecret($serviceSecret);
                $serviceSecret->setService(null);

                $deleted[spl_object_hash($serviceSecret)]= $serviceSecret;
            }

            foreach ($service->getVolumes() as $serviceVolume) {
                $service->removeVolume($serviceVolume);

                if ($projectVolume = $serviceVolume->getProjectVolume()) {
                    $projectVolume->removeServiceVolume($serviceVolume);
                }

                $deleted[spl_object_hash($serviceVolume)]= $serviceVolume;
            }

            foreach ($service->getChildren() as $child) {
                $deleted = array_merge($deleted, $deleteServices($child));
            }

            $project->removeService($service);

            $deleted[spl_object_hash($service)]= $service;

            return $deleted;
        };

        foreach ($project->getServices() as $service) {
            $deleted = array_merge($deleted, $deleteServices($service));
        }

        foreach ($project->getNetworks() as $network) {
            $project->removeNetwork($network);

            $deleted[spl_object_hash($network)]= $network;
        }

        foreach ($project->getSecrets() as $projectSecret) {
            $project->removeSecret($projectSecret);

            $deleted[spl_object_hash($projectSecret)]= $projectSecret;
        }

        foreach ($project->getVolumes() as $projectVolume) {
            $project->removeVolume($projectVolume);

            $deleted[spl_object_hash($projectVolume)]= $projectVolume;
        }

        $deleted[spl_object_hash($project)]= $project;

        $this->repo->delete(...array_values($deleted));
    }

    /**
     * @param User $user
     * @return array [id, name, service_count]
     */
    public function getList(User $user)
    {
        return $this->repo->getNamesAndCount($user);
    }
}
