<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BeanstalkdCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\CustomFileTrait;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice({"docker", "local"})
     */
    public $datastore;

    public $file = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        $this->validateFile($context);
    }

    protected function validateFile(ExecutionContextInterface $context)
    {
        foreach ($this->file as $filename => $contents) {
            if (empty(trim($contents ?? ''))) {
                $context->buildViolation("{$filename} cannot be empty")
                    ->atPath("file[{$filename}]")
                    ->addViolation();
            }
        }
    }
}
