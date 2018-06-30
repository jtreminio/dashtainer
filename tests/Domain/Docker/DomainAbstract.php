<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Entity\Docker as Entity;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DomainAbstract extends KernelTestCase
{
    /**
     * @return MockObject|ORM\EntityManagerInterface
     */
    protected function getEm() : MockObject
    {
        return $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();
    }

    protected function createNetwork(string $name) : Entity\Network
    {
        $entity = new Entity\Network();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId())
            ->setIsPublic(false)
            ->setIsEditable(true);

        return $entity;
    }

    protected function createPrivateNetwork() : Entity\Network
    {
        $entity = new Entity\Network();
        $entity->fromArray(['id' => 'private']);
        $entity->setName($entity->getId())
            ->setIsPublic(false)
            ->setIsEditable(false);

        return $entity;
    }

    protected function createPublicNetwork() : Entity\Network
    {
        $entity = new Entity\Network();
        $entity->fromArray(['id' => 'public']);
        $entity->setName($entity->getId())
            ->setIsPublic(true)
            ->setIsEditable(false);

        return $entity;
    }

    protected function createProject(string $name) : Entity\Project
    {
        $entity = new Entity\Project();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createProjectSecret(string $name) : Entity\Secret
    {
        $entity = new Entity\Secret();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceSecret(string $name) : Entity\ServiceSecret
    {
        $entity = new Entity\ServiceSecret();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createService(string $name) : Entity\Service
    {
        $entity = new Entity\Service();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceCategory(string $name) : Entity\ServiceCategory
    {
        $entity = new Entity\ServiceCategory();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceMeta(string $name) : Entity\ServiceMeta
    {
        $entity = new Entity\ServiceMeta();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceType(string $name) : Entity\ServiceType
    {
        $entity = new Entity\ServiceType();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceTypeMeta(string $name) : Entity\ServiceTypeMeta
    {
        $entity = new Entity\ServiceTypeMeta();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createPort(string $id, int $published, int $target) : Entity\ServicePort
    {
        $entity = new Entity\ServicePort();
        $entity->fromArray(['id' => $id]);
        $entity->setPublished($published)
            ->setTarget($target);

        return $entity;
    }

    protected function createProjectVolume(string $name) : Entity\Volume
    {
        $entity = new Entity\Volume();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }

    protected function createServiceVolume(string $name) : Entity\ServiceVolume
    {
        $entity = new Entity\ServiceVolume();
        $entity->fromArray(['id' => $name]);
        $entity->setName($entity->getId());

        return $entity;
    }
}
