<?php

namespace Dashtainer\Repository;

use Dashtainer\Entity;

use Doctrine\Common\Persistence;
use Doctrine\ORM;
use FOS\UserBundle\Doctrine;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util;

class UserManager extends Doctrine\UserManager
{
    protected const ENTITY_CLASS = Entity\User::class;

    /** @var ORM\EntityManagerInterface */
    protected $em;

    /** @var Persistence\ObjectRepository */
    protected $repo;

    public function __construct(
        Util\PasswordUpdaterInterface $passwordUpdater,
        Util\CanonicalFieldsUpdater $canonicalFieldsUpdater,
        Persistence\ObjectManager $om,
        string $class
    ) {
        $this->em   = $om;
        $this->repo = $om->getRepository(self::ENTITY_CLASS);

        parent::__construct($passwordUpdater, $canonicalFieldsUpdater, $om, $class);
    }

    /**
     * @inheritdoc
     * @return Entity\User|null
     */
    public function find($id) : ?Entity\User
    {
        return $this->repo->find($id);
    }

    /**
     * @inheritdoc
     * @return Entity\User[]
     */
    public function findAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @inheritdoc
     * @return Entity\User[]
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) : array {
        return $this->repo->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     * @return Entity\User|null
     */
    public function findOneBy(array $criteria) : ?Entity\User
    {
        return $this->repo->findOneBy($criteria);
    }

    public function getClassName() : string
    {
        return self::ENTITY_CLASS;
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function createUser()
    {
        return parent::createUser();
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function findUserBy(array $criteria)
    {
        return parent::findUserBy($criteria);
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function findUserByUsername($username)
    {
        return parent::findUserByUsername($username);
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function findUserByEmail($email)
    {
        return parent::findUserByEmail($email);
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        return parent::findUserByUsernameOrEmail($usernameOrEmail);
    }

    /**
     * {@inheritdoc}
     * @return Entity\User|UserInterface|null
     */
    public function findUserByConfirmationToken($token)
    {
        return parent::findUserByConfirmationToken($token);
    }

    /**
     * {@inheritdoc}
     * @return \Traversable|Entity\User[]
     */
    public function findUsers()
    {
        return parent::findUsers();
    }
}

