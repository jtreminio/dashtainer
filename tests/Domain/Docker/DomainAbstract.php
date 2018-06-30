<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DomainAbstract extends KernelTestCase
{
    protected function createNetwork(string $name) : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => $name]);
        $network->setName($name)
            ->setIsPublic(false)
            ->setIsEditable(true);

        return $network;
    }

    protected function createPrivateNetwork() : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => 'private']);
        $network->setName($network->getId())
            ->setIsPublic(false)
            ->setIsEditable(false);

        return $network;
    }

    protected function createPublicNetwork() : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => 'public']);
        $network->setName($network->getId())
            ->setIsPublic(true)
            ->setIsEditable(false);

        return $network;
    }

    protected function createProject(string $name) : Entity\Project
    {
        $project = new Entity\Project();
        $project->fromArray(['id' => $name]);
        $project->setName($project->getId());

        return $project;
    }

    protected function createProjectSecret(string $name) : Entity\Secret
    {
        $secret = new Entity\Secret();
        $secret->fromArray(['id' => $name]);
        $secret->setName($secret->getId());

        return $secret;
    }

    protected function createServiceSecret(string $name) : Entity\ServiceSecret
    {
        $secret = new Entity\ServiceSecret();
        $secret->fromArray(['id' => $name]);
        $secret->setName($secret->getId());

        return $secret;
    }

    protected function createService(string $name) : Entity\Service
    {
        $service = new Entity\Service();
        $service->fromArray(['id' => $name]);
        $service->setName($service->getId());

        return $service;
    }

    protected function createServiceCategory(string $name) : Entity\ServiceCategory
    {
        $category = new Entity\ServiceCategory();
        $category->fromArray(['id' => $name]);
        $category->setName($category->getId());

        return $category;
    }

    protected function createServiceMeta(string $name) : Entity\ServiceMeta
    {
        $meta = new Entity\ServiceMeta();
        $meta->fromArray(['id' => $name]);
        $meta->setName($meta->getId());

        return $meta;
    }

    protected function createServiceType(string $name) : Entity\ServiceType
    {
        $serviceType = new Entity\ServiceType();
        $serviceType->fromArray(['id' => $name]);
        $serviceType->setName($serviceType->getId());

        return $serviceType;
    }

    protected function createPort(string $id, int $published, int $target) : Entity\ServicePort
    {
        $port = new Entity\ServicePort();
        $port->fromArray(['id' => $id]);
        $port->setPublished($published)
            ->setTarget($target);

        return $port;
    }

    protected function createProjectVolume(string $name) : Entity\Volume
    {
        $volume = new Entity\Volume();
        $volume->fromArray(['id' => $name]);
        $volume->setName($volume->getId());

        return $volume;
    }

    protected function createServiceVolume(string $name) : Entity\ServiceVolume
    {
        $volume = new Entity\ServiceVolume();
        $volume->fromArray(['id' => $name]);
        $volume->setName($volume->getId());

        return $volume;
    }
}
