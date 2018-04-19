<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait UserFileTrait
{
    public $user_file = [];

    protected function validateUserFile(ExecutionContextInterface $context)
    {
        $filenames   = [];
        $fileTargets = [];

        foreach ($this->user_file as $key => $file) {
            $filename = trim($file['filename'] ?? '');
            $target   = trim($file['target'] ?? '');

            if (empty($filename)) {
                $context->buildViolation('Ensure all user config files have a filename')
                    ->atPath("user_file[{$key}][filename]")
                    ->addViolation();

                continue;
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all user config files have a target')
                    ->atPath("user_file[{$key}][target]")
                    ->addViolation();

                continue;
            }

            if (in_array($filename, $filenames)) {
                $context->buildViolation('Ensure all user config filenames are unique')
                    ->atPath("user_file[{$key}][filename]")
                    ->addViolation();

                continue;
            }

            if (in_array($target, $fileTargets)) {
                $context->buildViolation('Ensure all user config targets are unique')
                    ->atPath("user_file[{$key}][target]")
                    ->addViolation();

                continue;
            }

            $filenames   []= $filename;
            $fileTargets []= $target;
        }
    }
}
