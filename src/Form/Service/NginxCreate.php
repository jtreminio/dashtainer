<?php

namespace Dashtainer\Form\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NginxCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use CustomFileTrait;
    use ProjectFilesTrait;

    public $project_files = [];

    public $system_packages = [];

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
        if ($this->service_name_used) {
            $context->buildViolation('Name already used in this project')
                ->atPath('name')
                ->addViolation();
        }

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
