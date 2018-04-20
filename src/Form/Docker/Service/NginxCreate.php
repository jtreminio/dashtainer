<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NginxCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\ProjectFilesTrait;
    use DashAssert\SystemFileTrait;
    use DashAssert\UserFileTrait;

    public $project_files = [];

    public $system_packages = [];

    public $server_name;

    public $server_alias = [];

    public $document_root;

    public $fcgi_handler;

    public $vhost_conf;

    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        $this->validateProjectFiles($context);
        $this->validateSystemFile($context);
        $this->validateUserFile($context);
    }
}
