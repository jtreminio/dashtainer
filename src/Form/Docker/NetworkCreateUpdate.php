<?php

namespace Dashtainer\Form\Docker;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NetworkCreateUpdate implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a network name")
     */
    public $name;

    /**
     * @Assert\NotBlank(message = "Invalid project")
     */
    public $project;

    public $network_name_used = false;

    public $services = [];

    public $services_non_existant = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->network_name_used) {
            $context->buildViolation('Name already used in this project')
                ->atPath('name')
                ->addViolation();
        }

        if (!empty($this->services_non_existant)) {
            $err = 'Invalid service(s) selected: ' . implode(', ', $this->services_non_existant);

            $context->buildViolation($err)
                ->atPath('services')
                ->addViolation();
        }
    }
}
