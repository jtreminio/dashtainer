<?php

namespace Dashtainer\Tests\Form;

use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomFileMock
{
    use DashAssert\CustomFileTrait;

    public $custom_file = [];

    public function validate(ExecutionContextInterface $context, $payload)
    {
        $this->validateCustomFile($context);
    }
}
