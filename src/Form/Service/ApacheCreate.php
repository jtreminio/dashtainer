<?php

namespace Dashtainer\Form\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApacheCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\CustomFileTrait;
    use DashAssert\ProjectFilesTrait;

    public $project_files = [];

    public $system_packages = [];

    public $enabled_modules = [];

    public $disabled_modules = [];

    public $server_name;

    public $server_alias = [];

    public $document_root;

    public $fcgi_handler;

    public $vhost_conf;

    public $file = [];

    public $custom_file = [];

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        $this->validateProjectFiles($context);
        $this->validateFile($context);
        $this->validateCustomFile($context);
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
