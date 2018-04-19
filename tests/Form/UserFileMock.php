<?php

namespace Dashtainer\Tests\Form;

use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserFileMock
{
    use DashAssert\UserFileTrait;

    public function validate(ExecutionContextInterface $context, $payload)
    {
        $this->validateUserFile($context);
    }
}
