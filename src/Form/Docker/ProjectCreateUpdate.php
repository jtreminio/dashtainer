<?php

namespace Dashtainer\Form\Docker;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProjectCreateUpdate implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a project name")
     */
    public $name;

    public $project_name_used = false;

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->project_name_used) {
            $context->buildViolation('You already have a project with this name')
                ->atPath('name')
                ->addViolation();
        }
    }
}
