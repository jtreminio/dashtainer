<?php

namespace Dashtainer\Form;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;

abstract class DockerServiceCreateAbstract implements Util\HydratorInterface
{
    /**
     * @Assert\NotBlank(message = "Please enter a service name")
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      minMessage = "Please enter a service name",
     *      maxMessage = "Project service max length is {{ limit }} characters"
     * )
     * @todo enforce hostname regex
     */
    public $name;

    /**
     * @Assert\NotBlank(message = "Please choose a service type")
     */
    public $service_type;

    /**
     * @Assert\NotBlank(message = "Invalid project selected")
     */
    public $project;
}
