<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Entity;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class CreateAbstract implements Util\HydratorInterface
{
    /**
     * @DashAssert\NonBlankString(message = "Please enter a service name")
     * @DashAssert\Hostname
     */
    public $name;

    /**
     * @var Entity\Docker\Project
     * @Assert\NotBlank(message = "Invalid project selected")
     */
    public $project;

    public $service_name_used = false;

    /**
     * @Assert\NotBlank(message = "Please choose a service type")
     */
    public $type;

    // [id => [name, source, target, data]]
    public $volumes_file = [];

    // [id => [name, source, target, type]]
    public $volumes_other = [];

    public $volumes_granted = [];

    // [name]
    public $networks_create = [];

    // [id]
    public $networks_join = [];

    // [(id), name, contents]
    public $owned_secrets = [];

    public $owned_secrets_not_belong = [];

    // [id, target]
    public $grant_secrets = [];

    public $grant_secrets_not_belong = [];

    protected $usedSecretsNames = [];

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

        $this->validateNetworks($context);
        $this->validateSecrets($context);
        $this->validateVolumes($context);
    }

    protected function validateVolumes(ExecutionContextInterface $context)
    {
        $names   = [];
        $sources = [];
        $targets = [];

        /*
         * Fields:
         *      name
         *      source
         *      target
         *      type
         * Unique:
         *      name
         *      target
         */
        foreach ($this->volumes_other as $id => $volume) {
            $name   = trim($volume['name'] ?? '');
            $source = trim($volume['source'] ?? '');
            $target = trim($volume['target'] ?? '');
            $type   = trim($volume['type'] ?? '');

            if (empty($name)) {
                $context->buildViolation('Ensure all Volumes have a name')
                    ->atPath("volumes_other[{$id}][name]")
                    ->addViolation();
            }
            elseif (in_array($name, $names)) {
                $context->buildViolation('Ensure all Volume names are unique')
                    ->atPath("volumes_other[{$id}][name]")
                    ->addViolation();
            }

            if (empty($source)) {
                $context->buildViolation('Ensure all Volumes have a source')
                    ->atPath("volumes_other[{$id}][source]")
                    ->addViolation();
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all Volumes have a target')
                    ->atPath("volumes_other[{$id}][target]")
                    ->addViolation();
            }
            elseif (in_array($target, $targets)) {
                $context->buildViolation('Ensure all Volume targets are unique')
                    ->atPath("volumes_other[{$id}][target]")
                    ->addViolation();
            }

            if (empty($type)) {
                $context->buildViolation('Ensure all Volumes have a type')
                    ->atPath("volumes_other[{$id}][type]")
                    ->addViolation();
            }

            $names   []= $name;
            $sources []= $source;
            $targets []= $target;
        }

        /*
         * Fields:
         *      name
         *      source
         *      target (can be empty)
         *      data (can be empty)
         *      type
         * Unique:
         *      name
         *      source
         *      target
         */
        foreach ($this->volumes_file as $id => $volume) {
            $name   = trim($volume['name'] ?? '');
            $source = trim($volume['source'] ?? '');
            $target = trim($volume['target'] ?? '');
            $type   = trim($volume['type'] ?? '');

            if (empty($name)) {
                $context->buildViolation('Ensure all Files have a name')
                    ->atPath("volumes_file[{$id}][name]")
                    ->addViolation();
            }
            elseif (in_array($name, $names)) {
                $context->buildViolation('Ensure all Files names are unique')
                    ->atPath("volumes_file[{$id}][name]")
                    ->addViolation();
            }

            if (empty($source)) {
                $context->buildViolation('Ensure all Files have a source')
                    ->atPath("volumes_file[{$id}][source]")
                    ->addViolation();
            }
            elseif (in_array($source, $sources)) {
                $context->buildViolation('Ensure all File sources are unique')
                    ->atPath("volumes_file[{$id}][source]")
                    ->addViolation();
            }

            if (!empty($target) && in_array($target, $targets)) {
                $context->buildViolation('Ensure all File targets are unique')
                    ->atPath("volumes_file[{$id}][target]")
                    ->addViolation();
            }

            if (empty($type)) {
                $context->buildViolation('Ensure all Files have a type')
                    ->atPath("volumes_file[{$id}][type]")
                    ->addViolation();
            }

            $names   []= $name;
            $sources []= $source;
            $targets []= $target;
        }

        /*
         * Fields:
         *      id (checkbox to enable/disable)
         *      target
         * Unique:
         *      target
         */
        foreach ($this->volumes_granted as $key => $volume) {
            $id     = trim($volume['id'] ?? '');
            $target = trim($volume['target'] ?? '');

            if (empty($id)) {
                unset($this->volumes_granted[$key]);

                continue;
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all Granted Volumes have a target')
                    ->atPath("volumes_granted[{$id}][target]")
                    ->addViolation();
            }
            elseif (in_array($target, $targets)) {
                $context->buildViolation('Ensure all Granted Volume targets are unique')
                    ->atPath("volumes_granted[{$id}][target]")
                    ->addViolation();
            }

            $targets []= $target;
        }
    }

    protected function validateNetworks(ExecutionContextInterface $context)
    {
        if (empty($this->networks_create) && empty($this->networks_join)) {
            $context->buildViolation('You must join or create at least one Network')
                ->atPath('networks')
                ->addViolation();
        }

        foreach ($this->networks_create as $id => $networkName) {
            if (empty($networkName)) {
                $context->buildViolation('You must enter a name for this Network')
                    ->atPath("networks_create-{$id}-name")
                    ->addViolation();
            }

            $error = $context->getValidator()->validate(
                $networkName,
                new DashAssert\Hostname()
            );

            if (count($error) > 0) {
                $context->buildViolation(
                    'You must enter a name for this Network, valid characters are a-zA-Z0-9 and _'
                )
                    ->atPath("networks_create-{$id}-name")
                    ->addViolation();
            }
        }
    }

    protected function validateSecrets(ExecutionContextInterface $context)
    {
        $this->usedSecretsNames = [];

        $this->validateOwnedSecrets($context);
        $this->validateGrantSecrets($context);
    }

    protected function validateOwnedSecrets(ExecutionContextInterface $context)
    {
        foreach ($this->owned_secrets as $id => $secret) {
            if (empty($secret['name'])) {
                $context->buildViolation('You must enter a name for this Secret')
                    ->atPath("owned_secrets-{$id}-name")
                    ->addViolation();
            }

            $error = $context->getValidator()->validate(
                $secret['name'] ?? '',
                new DashAssert\SecretName()
            );

            if (count($error) > 0) {
                $context->buildViolation(
                    'You must enter a name for this Secret, valid characters are a-zA-Z0-9 _ and -'
                )
                    ->atPath("owned_secrets-{$id}-name")
                    ->addViolation();
            }

            if (!empty($secret['name'])) {
                if (in_array($secret['name'], $this->usedSecretsNames)) {
                    $context->buildViolation('Duplicate Secret name found')
                        ->atPath("owned_secrets-{$id}-name")
                        ->addViolation();
                }

                $this->usedSecretsNames []= $secret['name'];
            }
        }

        foreach ($this->owned_secrets_not_belong as $id) {
            $context->buildViolation('This Secret belongs to another Service')
                ->atPath("owned_secrets-{$id}-name")
                ->addViolation();
        }
    }

    protected function validateGrantSecrets(ExecutionContextInterface $context)
    {
        foreach ($this->grant_secrets as $id => $secret) {
            if (empty($secret['grant'])) {
                continue;
            }

            if (empty($secret['target'])) {
                $context->buildViolation('You must enter a target filename for this Secret')
                    ->atPath("grant_secrets-{$id}-target")
                    ->addViolation();
            }

            $error = $context->getValidator()->validate(
                $secret['target'] ?? '',
                new DashAssert\SecretName()
            );

            if (count($error) > 0) {
                $context->buildViolation(
                    'You must enter a target filename for this Secret, valid characters are a-zA-Z0-9 _ and -'
                )
                    ->atPath("grant_secrets-{$id}-target")
                    ->addViolation();
            }

            if (!empty($secret['target'])) {
                if (in_array($secret['target'], $this->usedSecretsNames)) {
                    $context->buildViolation('Duplicate Secret target filename found')
                        ->atPath("grant_secrets-{$id}-target")
                        ->addViolation();
                }

                $this->usedSecretsNames []= $secret['target'];
            }
        }

        foreach ($this->grant_secrets_not_belong as $id) {
            $context->buildViolation('This Secret belongs to another Project')
                ->atPath("grant_secrets-{$id}-target")
                ->addViolation();
        }
    }
}
