<?php

namespace DashtainerBundle\Domain;

use DashtainerBundle\Entity;

use FOS\UserBundle\Doctrine;
use FOS\UserBundle\Model\UserInterface;

class UserManager extends Doctrine\UserManager
{
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

