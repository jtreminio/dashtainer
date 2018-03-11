<?php

namespace Dashtainer\Form\Service;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait CustomFileTrait
{
    public $custom_file = [];

    protected function validateCustomFile(ExecutionContextInterface $context)
    {
        $filenames   = [];
        $fileTargets = [];

        foreach ($this->custom_file as $key => $file) {
            $filename = trim($file['filename']) ?? '';
            $target   = trim($file['target'] ?? '');

            if (empty($filename)) {
                $context->buildViolation('Ensure all custom config files have a filename')
                    ->atPath("custom_file[{$key}][filename]")
                    ->addViolation();
            }

            if (!empty($filename) && in_array($filename, $filenames)) {
                $context->buildViolation('Ensure all custom config filenames are unique')
                    ->atPath("custom_file[{$key}][filename]")
                    ->addViolation();
            }

            if (!empty($filename)) {
                $filenames []= $filename;
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all custom config files have a target')
                    ->atPath("custom_file[{$key}][target]")
                    ->addViolation();
            }

            if (!empty($target) && in_array($target, $fileTargets)) {
                $context->buildViolation('Ensure all custom config targets are unique')
                    ->atPath("custom_file[{$key}][target]")
                    ->addViolation();
            }

            if (!empty($target)) {
                $fileTargets []= $target;
            }
        }
    }
}
