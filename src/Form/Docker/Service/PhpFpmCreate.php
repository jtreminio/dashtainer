<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PhpFpmCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    /**
     * @DashAssert\NonBlankString(message = "Version must be chosen")
     */
    public $version;

    public $ini = [];

    public $xdebug = false;

    public $blackfire = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        $this->validateBlackfire($context);
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
