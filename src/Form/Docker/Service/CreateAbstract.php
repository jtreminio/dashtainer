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
