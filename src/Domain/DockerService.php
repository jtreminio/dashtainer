<?php

namespace Dashtainer\Domain;

use Dashtainer\Entity;
use Dashtainer\Form;

use Doctrine\ORM\EntityManagerInterface;

class DockerService
{
    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createServiceFromForm(
        Form\DockerServiceCreateForm $form
    ) : Entity\DockerService {
        $service = new Entity\DockerService();
        $service->fromArray($form->toArray());

        $this->em->persist($service);
        $this->em->flush();

        return $service;
    }
}
