<?php

namespace Dashtainer\Entity;

use Dashtainer\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Table(name="`user`")
 * @ORM\Entity
 */
class User extends BaseUser implements Util\HydratorInterface, EntityBaseInterface
{
    use Util\HydratorTrait;
    use EntityBaseTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Dashtainer\Entity\DockerProject", mappedBy="user")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    protected $projects;

    public function __construct()
    {
        parent::__construct();

        $this->projects = new Collections\ArrayCollection();
    }

    /**
     * @param DockerProject $project
     * @return $this
     */
    public function addProject(DockerProject $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    public function removeProject(DockerProject $project)
    {
        $this->projects->removeElement($project);
    }

    /**
     * @return DockerProject[]|Collections\ArrayCollection
     */
    public function getProjects()
    {
        return $this->projects;
    }
}
