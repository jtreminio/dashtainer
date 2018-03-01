<?php

namespace Dashtainer\Form;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;

class DockerNetworkCreateUpdateForm
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Please enter a network name.")
     */
    public $name;

    /**
     * @Assert\Choice({"bridge", "overlay"}, message="Please choose a valid driver option.")
     */
    public $driver;

    /**
     * @Assert\Choice({"true"}, message="Please choose a valid external option.")
     */
    public $external;

    /**
     * @Assert\NotBlank(message = "Invalid project selected.")
     */
    public $project;
}
