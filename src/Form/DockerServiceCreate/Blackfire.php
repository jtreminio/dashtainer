<?php

namespace Dashtainer\Form\DockerServiceCreate;

use Dashtainer\Form;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Blackfire extends Form\DockerServiceCreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Please enter your Blackfire Server ID.")
     */
    public $server_id;

    /**
     * @DashAssert\NonBlankString(message = "Please enter your Blackfire Server Token.")
     */
    public $server_token;

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
    }
}
