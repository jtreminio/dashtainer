<?php

namespace DashtainerBundle\Form;

use DashtainerBundle\Util;

use Symfony\Component\Validator\Constraints as Assert;

class DockerProjectCreateForm
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Please enter a project name.")
     */
    public $name;
}
