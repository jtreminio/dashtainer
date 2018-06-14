<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostgreSQLCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Version must be chosen")
     */
    public $version;

    public $port_confirm;

    public $port;

    public $port_used = false;

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

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        $this->validatePort($context);
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

        if ($this->port < 5432 || $this->port > 65535) {
            $context->buildViolation('Port must be between 5432 and 65535')
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
