<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait CustomFileTrait
{
    public $custom_file = [];

    protected function validateCustomFile(ExecutionContextInterface $context)
    {
        $filenames   = [];
        $fileTargets = [];

        foreach ($this->custom_file as $key => $file) {
            $filename = trim($file['filename'] ?? '');
            $target   = trim($file['target'] ?? '');

            if (empty($filename)) {
                $context->buildViolation('Ensure all custom config files have a filename')
                    ->atPath("custom_file[{$key}][filename]")
                    ->addViolation();

                continue;
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all custom config files have a target')
                    ->atPath("custom_file[{$key}][target]")
                    ->addViolation();

                continue;
            }

            if (in_array($filename, $filenames)) {
                $context->buildViolation('Ensure all custom config filenames are unique')
                    ->atPath("custom_file[{$key}][filename]")
                    ->addViolation();

                continue;
            }

            if (in_array($target, $fileTargets)) {
                $context->buildViolation('Ensure all custom config targets are unique')
                    ->atPath("custom_file[{$key}][target]")
                    ->addViolation();

                continue;
            }

            $filenames   []= $filename;
            $fileTargets []= $target;
        }
    }
}
