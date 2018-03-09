<?php

namespace Dashtainer\Form\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class CreateAbstract implements Util\HydratorInterface
{
    /**
     * @DashAssert\NonBlankString(message = "Please enter a service name")
     * @DashAssert\Hostname
     */
    public $name;

    /**
     * @Assert\NotBlank(message = "Invalid project selected")
     */
    public $project;

    public $service_name_used = false;

    /**
     * @Assert\NotBlank(message = "Please choose a service type")
     */
    public $type;

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->service_name_used) {
            $context->buildViolation('Name already used in this project')
                ->atPath('name')
                ->addViolation();
        }
    }
}
