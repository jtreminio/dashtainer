<?php

namespace Dashtainer\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sessions")
 * @ORM\Entity()
 */
class Sessions
{
    /**
     * @ORM\Id
     * @ORM\Column(name="sess_id", type="varbinary", unique=true)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $sess_id;

    /**
     * @ORM\Column(name="sess_data", type="blob", nullable=false)
     */
    protected $sess_data;

    /**
     * @ORM\Column(name="sess_time", type="integer", options={"unsigned": true}, nullable=false)
     */
    protected $sess_time;

    /**
     * @ORM\Column(name="sess_lifetime", type="integer", nullable=false)
     */
    protected $sess_lifetime;
}
