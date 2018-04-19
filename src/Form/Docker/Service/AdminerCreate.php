<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AdminerCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\ProjectFilesTrait;
    use DashAssert\UserFileTrait;

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

        $this->validateUserFile($context);
    }
}
