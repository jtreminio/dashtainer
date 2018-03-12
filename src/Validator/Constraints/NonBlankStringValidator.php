<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NonBlankStringValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (empty(trim($value ?? ''))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
