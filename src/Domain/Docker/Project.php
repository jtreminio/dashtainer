<?php

namespace Dashtainer\Domain\Docker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;

class Project
{
    /** @var Repository\Docker\Project */
    protected $repo;

    public function __construct(
        Repository\Docker\Project $repo
    ) {
        $this->repo = $repo;
    }

    public function createProjectFromForm(
        Form\Docker\ProjectCreateUpdate $form,
        Entity\User $user
    ) : Entity\Docker\Project {
        $project = new Entity\Docker\Project();
        $project->fromArray($form->toArray());
        $project->setUser($user);

        $this->repo->save($project);

        $hostname = Util\Strings::hostname($project->getSlug());

        $publicNetwork = new Entity\Docker\Network();
        $publicNetwork->setName("{$hostname}-public")
            ->setIsEditable(false)
            ->setIsPublic(true)
            ->setExternal('traefik_webgateway')
            ->setProject($project);

        $project->addNetwork($publicNetwork);

        $this->repo->save($publicNetwork, $project);

        return $project;
    }

    public function delete(Entity\Docker\Project $project)
    {
        $deleted = [];

        $deleteServices = function(Entity\Docker\Service $service)
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

            foreach ($service->getVolumes() as $volume) {
                $service->removeVolume($volume);

                if ($projectVolume = $volume->getProjectVolume()) {
                    $volume->setProjectVolume(null);
                    $projectVolume->removeServiceVolume($volume);
                }

                $deleted[spl_object_hash($volume)]= $volume;
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

        foreach ($project->getVolumes() as $volume) {
            $project->removeVolume($volume);

            $deleted[spl_object_hash($volume)]= $volume;
        }

        $deleted[spl_object_hash($project)]= $project;

        $this->repo->delete(...array_values($deleted));
    }
}
