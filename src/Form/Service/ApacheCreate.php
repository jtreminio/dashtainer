<?php

namespace Dashtainer\Form\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApacheCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter the source of your project files")
     */
    public $directory;

    public $system_packages = [];

    public $enabled_modules = [];

    public $disabled_modules = [];

    public $server_name;

    public $server_alias = [];

    public $document_root;

    public $fcgi_handler;

    public $vhost_conf;

    public $file = [];

    public $custom_file = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->service_name_used) {
            $context->buildViolation('Name already used in this project')
                ->atPath('name')
                ->addViolation();
        }

        $this->validateFile($context);
        $this->validateCustomFile($context);
    }

    protected function validateFile(ExecutionContextInterface $context)
    {
        foreach ($this->file as $filename => $contents) {
            if (empty(trim($contents ?? ''))) {
                $context->buildViolation("{$filename} cannot be empty")
                    ->atPath("file[{$filename}]")
                    ->addViolation();
            }
        }
    }

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
