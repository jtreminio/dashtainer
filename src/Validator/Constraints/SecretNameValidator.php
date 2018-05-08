<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SecretNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);

        if (preg_match('/[^a-zA-Z0-9\-_]/i', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();

            return;
        }

        if (strlen($value) < 2 || strlen($value) > 64) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
