<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostgreSQLCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\CustomFileTrait;

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
     * @DashAssert\NonBlankString(message = "Please enter a database name")
     * @DashAssert\Hostname
     */
    public $postgres_db;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a Postgres user")
     * @DashAssert\Hostname
     */
    public $postgres_user;

    /**
     * @DashAssert\NonBlankString(message = "Please enter a user password")
     * @DashAssert\Hostname
     */
    public $postgres_password;

    public $file = [];

    public $custom_file = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

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
}
