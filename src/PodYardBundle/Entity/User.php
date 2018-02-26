<?php

namespace PodYardBundle\Entity;

use PodYardBundle\Util;

use Doctrine\Common\Collections;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
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
     * @ORM\OneToMany(targetEntity="PodYardBundle\Entity\Project", mappedBy="user")
     */
    protected $projects;

    public function __construct()
    {
        parent::__construct();

        $this->projects = new Collections\ArrayCollection();
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function addProject(Project $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    /**
     * @param Project $project
     */
    public function removeProject(Project $project)
    {
        $this->projects->removeElement($project);
    }

    /**
     * @return Project[]|Collections\ArrayCollection
     */
    public function getProjects()
    {
        return $this->projects;
    }
}
