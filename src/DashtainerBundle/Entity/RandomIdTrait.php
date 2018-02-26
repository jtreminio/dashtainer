<?php

namespace DashtainerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait RandomIdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=8)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="DashtainerBundle\Doctrine\RandomIdGenerator")
     */
    protected $id;
}
