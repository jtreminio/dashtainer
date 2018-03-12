<?php

namespace Dashtainer\Form;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;

class DockerNetworkCreateUpdateForm implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a network name")
     * @DashAssert\Hostname
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
