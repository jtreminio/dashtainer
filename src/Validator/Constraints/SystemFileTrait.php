<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SystemFileTrait
{
    public $system_file = [];

    protected function validateSystemFile(ExecutionContextInterface $context)
    {
        foreach ($this->system_file as $filename => $contents) {
            if (empty(trim($contents ?? ''))) {
                $context->buildViolation("{$filename} cannot be empty")
                    ->atPath("system_file[{$filename}]")
                    ->addViolation();
            }
        }
    }
}
