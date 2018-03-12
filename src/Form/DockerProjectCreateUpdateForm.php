<?php

namespace Dashtainer\Form;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;

class DockerProjectCreateUpdateForm implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a project name")
     */
    public $name;
}
