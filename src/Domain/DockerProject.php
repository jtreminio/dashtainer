<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class DockerProject
{
    /** @var Repository\DockerProjectRepository */
    protected $repo;

    public function __construct(Repository\DockerProjectRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createProjectFromForm(
        Form\DockerProjectCreateUpdateForm $form,
        Entity\User $user
    ) : Entity\DockerProject {
        $project = new Entity\DockerProject();
        $project->fromArray($form->toArray());
        $project->setUser($user);

        $this->repo->save($project);

        $webNetwork = new Entity\DockerNetwork();
        $webNetwork->setName('web')
            ->setExternal('traefik_webgateway')
            ->setProject($project);

        $projectNetwork = new Entity\DockerNetwork();
        $projectNetwork->setName($project->getSlug())
            ->setProject($project);

        $project->addNetwork($webNetwork)
            ->addNetwork($projectNetwork);

        $this->repo->save($webNetwork, $projectNetwork, $project);

        return $project;
    }
}
