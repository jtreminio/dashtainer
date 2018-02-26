<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="DashtainerBundle\Repository\ProjectRepository")
 */
class Project implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=8)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="DashtainerBundle\Doctrine\RandomIdGenerator")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="DashtainerBundle\Entity\User", inversedBy="projects")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    public function getUser() : ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }
}
