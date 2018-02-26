<?php

namespace DashtainerBundle\Entity;

use DashtainerBundle\Util;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait EntityBaseTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated_at;

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedAt() : ?\DateTime
    {
        return $this->created_at;
    }

    /**
     * @param string|\DateTime $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = Util\DateTime::toDateTime($created_at);

        return $this;
    }

    public function getUpdatedAt() : ?\DateTime
    {
        return $this->updated_at;
    }

    /**
     * @param string|\DateTime $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = Util\DateTime::toDateTime($updated_at);

        return $this;
    }
}
