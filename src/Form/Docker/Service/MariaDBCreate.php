<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MariaDBCreate extends CreateAbstract implements Util\HydratorInterface
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

    public $port_confirm;

    public $port;

    public $port_used = false;

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
        parent::validate($context, $payload);

        $this->validateFile($context);
        $this->validateCustomFile($context);
        $this->validatePort($context);
    }

    protected function validateFile(ExecutionContextInterface $context)
    {
        if (empty(trim($this->file['my.cnf'] ?? ''))) {
            $context->buildViolation('my.cnf cannot be empty')
                ->atPath('file[my.cnf]')
                ->addViolation();
        }
    }

    protected function validatePort(ExecutionContextInterface $context)
    {
        if (!$this->port_confirm) {
            return;
        }

        if (empty($this->port) || !is_numeric($this->port)) {
            $context->buildViolation('You must enter a port')
                ->atPath('port')
                ->addViolation();

            return;
        }

        if ($this->port < 3306 || $this->port > 65535) {
            $context->buildViolation('Port must be between 3306 and 65535')
                ->atPath('port')
                ->addViolation();
        }

        if ($this->port_used) {
            $context->buildViolation('Port already used in a different service')
                ->atPath('port')
                ->addViolation();
        }
    }
}
