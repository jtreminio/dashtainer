<?php

namespace Dashtainer\Form\DockerServiceCreate;

use Dashtainer\Form;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PhpFpm extends Form\DockerServiceCreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Version must be chosen")
     */
    public $version;

    /**
     * @DashAssert\NonBlankString(message = "Please enter the source of your project files")
     */
    public $directory;

    public $php_packages = [];

    public $pear_packages = [];

    public $pecl_packages = [];

    public $system_packages = [];

    public $file = [];

    public $composer = [];

    public $xdebug = [];

    public $blackfire = [];

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
        $this->validateXdebug($context);
        $this->validateBlackfire($context);
    }

    protected function validateFile(ExecutionContextInterface $context)
    {
        if (empty(trim($this->file['fpm.conf'] ?? ''))) {
            $context->buildViolation('fpm.conf cannot be empty')
                ->atPath('file[fpm.conf]')
                ->addViolation();
        }

        if (empty(trim($this->file['fpm_pool.conf'] ?? ''))) {
            $context->buildViolation('fpm_pool.conf cannot be empty')
                ->atPath('file[fpm_pool.conf]')
                ->addViolation();
        }
    }

    protected function validateXdebug(ExecutionContextInterface $context)
    {
        if (empty($this->xdebug['install'])) {
            return;
        }

        if (empty(trim($this->xdebug['ini'] ?? ''))) {
            $context->buildViolation('Xdebug INI cannot be empty')
                ->atPath('xdebug[ini]')
                ->addViolation();
        }
    }

    protected function validateBlackfire(ExecutionContextInterface $context)
    {
        if (empty($this->blackfire['install'])) {
            return;
        }

        if (empty(trim($this->blackfire['server_id'] ?? ''))) {
            $context->buildViolation('Blackfire Server ID cannot be empty')
                ->atPath('blackfire[server_id]')
                ->addViolation();
        }

        if (empty(trim($this->blackfire['server_token'] ?? ''))) {
            $context->buildViolation('Blackfire Server Token cannot be empty')
                ->atPath('blackfire[server_token]')
                ->addViolation();
        }
    }
}
