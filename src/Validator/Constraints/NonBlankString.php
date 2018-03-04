<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NonBlankString extends Constraint
{
    public $message = 'The string "{{ string }}" must not be an empty (whitespace) value.';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
