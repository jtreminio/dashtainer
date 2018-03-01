<?php

namespace Dashtainer\Form;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;

class DockerProjectCreateUpdateForm
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Please enter a project name.")
     */
    public $name;
}
