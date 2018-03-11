<?php

namespace Dashtainer\Form\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MariaDBCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Version must be chosen")
     */
    public $version;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice({"docker", "local"})
     */
    public $datastore;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a root password")
     * @DashAssert\Hostname
     */
    public $mysql_root_password;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a database name")
     * @DashAssert\Hostname
     */
    public $mysql_database;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a MySQL user")
     * @DashAssert\Hostname
     */
    public $mysql_user;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a user password")
     * @DashAssert\Hostname
     */
    public $mysql_password;

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
        if (empty(trim($this->file['my.cnf'] ?? ''))) {
            $context->buildViolation('my.cnf cannot be empty')
                ->atPath('file[my.cnf]')
                ->addViolation();
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
