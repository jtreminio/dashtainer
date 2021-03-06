<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApacheVhost implements Util\HydratorInterface
{
    use Util\HydratorTrait;

    public $server_name;

    public $server_alias = [];

    public $document_root;

    public $handler;

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
    }
}
