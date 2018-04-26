<?php

namespace Dashtainer\Migrations\v1_0_4;

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

        $this->getServiceCategories();

        $this->loadServiceTypes();
    }

    protected function getServiceCategories()
    {
        $repo = $this->em->getRepository(Entity\Docker\ServiceCategory::class);

        foreach ($repo->findAll() as $entity) {
            $this->addReference('service_category-' . $entity->getName(), $entity);
        }
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

    public function getOrder()
    {
        return 1;
    }
}
