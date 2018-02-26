<?php

namespace DashtainerBundle\Migrations\v1_0_0;

use DashtainerBundle\Entity;
use DashtainerBundle\Migrations\DataLoader;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DataFixtures
    extends AbstractFixture
    implements OrderedFixtureInterface
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

    public function getOrder()
    {
        return 1;
    }
}
