<?php

namespace Dashtainer\Form;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;

class DockerProjectCreateUpdateForm
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Please enter a project name.")
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      minMessage = "Please enter a project name.",
     *      maxMessage = Project name max length is {{ limit }} characters."
     * )
     * @todo enforce hostname regex
     */
    public $name;
}
