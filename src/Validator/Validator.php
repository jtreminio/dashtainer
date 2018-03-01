<?php

namespace Dashtainer\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator as SymfonyValidator;

class Validator
{
    /** @var ConstraintViolation[] */
    protected $customErrors = [];

    /** @var ConstraintViolation[] */
    protected $errors = [];

    protected $source;

    /** @var SymfonyValidator\ValidatorInterface */
    protected $validator;

    public function __construct(SymfonyValidator\ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function setSource(object $source)
    {
        $this->source = $source;
    }

    public function isValid() : bool
    {
        $this->errors = $this->validator->validate($this->source);

        return empty($this->errors->count() + count($this->customErrors));
    }

    public function addError(string $element, string $msg)
    {
        $error = new ConstraintViolation(
            $msg, $msg, [], $this->source, $element, ''
        );

        $this->customErrors[] = $error;
    }

    /**
     * Returns validation errors as array
     *
     * @param bool $shift Return only first error per element
     * @return array
     */
    public function getErrors(bool $shift = false) : array
    {
        $errors = [];

        foreach ($this->errors as $violation) {
            if ($shift && !empty($errors[$violation->getPropertyPath()])) {
                continue;
            }

            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        foreach ($this->customErrors as $violation) {
            if ($shift && !empty($errors[$violation->getPropertyPath()])) {
                continue;
            }

            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getErrorsObject() : array
    {
        return $this->errors;
    }
}
