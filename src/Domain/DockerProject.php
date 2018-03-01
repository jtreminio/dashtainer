<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;

use Doctrine\ORM\EntityManagerInterface;

class DockerProject
{
    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createProjectFromForm(
        Form\DockerProjectCreateForm $form,
        Entity\User $user
    ) : Entity\DockerProject {
        $project = new Entity\DockerProject();
        $project->fromArray($form->toArray());
        $project->setUser($user);

        $this->em->persist($project);

        $webNetwork = new Entity\DockerNetwork();
        $webNetwork->setName('web')
            ->setExternal('traefik_webgateway')
            ->setProject($project);

        $projectNetwork = new Entity\DockerNetwork();
        $projectNetwork->setName($project->getSlug())
            ->setProject($project);

        $project->addNetwork($webNetwork)
            ->addNetwork($projectNetwork);

        $this->em->persist($webNetwork);
        $this->em->persist($projectNetwork);
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }
}
