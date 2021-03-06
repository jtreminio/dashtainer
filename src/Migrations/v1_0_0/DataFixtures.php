<?php

namespace Dashtainer\Migrations\v1_0_0;

use Dashtainer\Entity;
use Dashtainer\Migrations\DataLoader;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DataFixtures extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var ObjectManager */
    protected $em;

    /** @var DataLoader */
    protected $dataLoader;

    public function __construct(DataLoader $dataLoader)
    {
        $this->dataLoader = $dataLoader;
        $this->dataLoader->setBaseDir(__DIR__ . '/data');
    }

    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        $this->loadUsers();
        $this->loadServiceCategories();
        $this->loadServiceTypes();
        $this->loadServiceTypeMetas();
    }

    protected function loadUsers()
    {
        foreach ($this->dataLoader->getData('users') as $row) {
            $entity = new Entity\User();
            $entity->fromArray($row);

            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    protected function loadServiceCategories()
    {
        foreach ($this->dataLoader->getData('service_categories') as $row) {
            $entity = new Entity\Docker\ServiceCategory();
            $entity->fromArray($row);

            $this->em->persist($entity);

            $referenceName = "service_category-{$entity->getName()}";
            $this->addReference($referenceName, $entity);
        }

        $this->em->flush();
    }

    protected function loadServiceTypes()
    {
        foreach ($this->dataLoader->getData('service_types') as $row) {
            $row['category'] = $this->getReference(
                "service_category-{$row['category']}"
            );

            $entity = new Entity\Docker\ServiceType();
            $entity->fromArray($row);

            $this->em->persist($entity);

            $referenceName = "service_type-{$entity->getName()}";
            $this->addReference($referenceName, $entity);
        }

        $this->em->flush();
    }

    protected function loadServiceTypeMetas()
    {
        foreach ($this->dataLoader->getData('service_type_metas') as $row) {
            $row['type'] = $this->getReference(
                "service_type-{$row['type']}"
            );

            $entity = new Entity\Docker\ServiceTypeMeta();
            $entity->fromArray($row);

            $this->em->persist($entity);

            $serviceTypeName = $row['type']->getName();

            $referenceName = "service_type_meta-{$serviceTypeName}-{$entity->getName()}";
            $this->addReference($referenceName, $entity);
        }

        $this->em->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
