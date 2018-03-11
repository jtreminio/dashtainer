<?php

namespace Dashtainer\Form\Service;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait ProjectFilesTrait
{
    public $project_files = [];

    public function validateProjectFiles(ExecutionContextInterface $context)
    {
        if (empty(trim($this->project_files['type'] ?? ''))) {
            $context->buildViolation('Project files source type must be chosen')
                ->atPath('project_files[type]')
                ->addViolation();

            return;
        }

        if ($this->project_files['type'] == 'local') {
            $this->validateProjectFilesLocal($context);

            return;
        }
    }

    public function validateProjectFilesLocal(ExecutionContextInterface $context)
    {
        if (empty(trim($this->project_files['local']['source'] ?? ''))) {
            $context->buildViolation('Path to Project Files must be defined')
                ->atPath('project_files[local][source]')
                ->addViolation();
        }
    }
}
