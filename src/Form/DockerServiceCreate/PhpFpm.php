<?php

namespace Dashtainer\Form\DockerServiceCreate;

use Dashtainer\Form;
use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PhpFpm extends Form\DockerServiceCreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank(message = "Version must be chosen")
     */
    public $version;

    /**
     * @Assert\NotBlank(message = "Please enter the source of your project files")
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
    }
}
