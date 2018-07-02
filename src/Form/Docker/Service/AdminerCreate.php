<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AdminerCreate extends CreateAbstract
{
    use Util\HydratorTrait;

    /**
     * @Assert\NotBlank()
     */
    public $design;

    public $plugins = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);
    }
}
