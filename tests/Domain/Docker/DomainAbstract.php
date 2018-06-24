<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DomainAbstract extends KernelTestCase
{
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

    protected function createNetwork(string $name) : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => $name]);
        $network->setName($name)
            ->setIsPublic(false)
            ->setIsEditable(true);

        return $network;
    }

    protected function createProjectSecret(string $name) : Entity\Secret
    {
        $secret = new Entity\Secret();
        $secret->fromArray(['id' => $name]);
        $secret->setName($secret->getId());

        return $secret;
    }

    protected function createServiceSecrete(string $name) : Entity\ServiceSecret
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
}
