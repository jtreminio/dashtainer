<?php

namespace DashtainerBundle\Form;

use DashtainerBundle\Util;

use Symfony\Component\Validator\Constraints as Assert;

class ServiceCreateForm
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Please enter a service name.")
     */
    public $name;
}
