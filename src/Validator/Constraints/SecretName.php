<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SecretName extends Constraint
{
    public $message = 'The string "{{ string }}" must be between 2 and 64 characters and contain only a-zA-Z0-9_ and "-"';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
