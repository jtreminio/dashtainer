<?php

namespace Dashtainer\Form\DockerServiceCreate;

use Dashtainer\Form;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;

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
}
