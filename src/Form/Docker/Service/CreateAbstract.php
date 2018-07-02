<?php

namespace Dashtainer\Form\Docker\Service;

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

    public $service_name_used = false;

    // [id, name]
    public $networks = [];

    // [id => [published, target, protocol]]
    public $ports = [];

    // [tcp => [], udp => []]
    public $ports_used = [
        'tcp' => [],
        'udp' => [],
    ];

    // [serviceSecret.id, serviceSecret.name, projectSecret.data]
    public $secrets = [];

    // [projectSecret.id, serviceSecret.name]
    public $secrets_granted = [];

    // [id => [name, source, target, data]]
    public $volumes_file = [];

    // [id => [name, source, target, type]]
    public $volumes_other = [];

    public $volumes_granted = [];

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
        $this->validatePorts($context);
        $this->validateSecrets($context);
        $this->validateVolumes($context);
    }

    protected function validateNetworks(ExecutionContextInterface $context)
    {
        $names = [];

        /*
         * Fields:
         *      id
         *      name
         * Unique:
         *      name
         */
        foreach ($this->networks as $id => $network) {
            $name = trim($network['name'] ?? '');

            if (empty($name)) {
                $context->buildViolation('Ensure all Networks have a name')
                    ->atPath("networks[{$id}][name]")
                    ->addViolation();
            }
            elseif (in_array($name, $names)) {
                $context->buildViolation('Ensure all Network names are unique')
                    ->atPath("networks[{$id}][name]")
                    ->addViolation();
            }

            $names []= $name;
        }
    }

    protected function validatePorts(ExecutionContextInterface $context)
    {
        $publisheds = [
            'tcp' => [],
            'udp' => [],
        ];
        $targets    = [
            'tcp' => [],
            'udp' => [],
        ];

        /*
         * Fields:
         *      published
         *      target
         *      protocol
         *      mode
         * Unique:
         *      published (per protocol)
         *      target (per protocol)
         */
        foreach ($this->ports as $id => $port) {
            $published = trim($port['published'] ?? '');
            $target    = trim($port['target'] ?? '');
            $protocol  = trim($port['protocol'] ?? 'tcp');

            if (empty($published)) {
                unset($this->ports[$id]);

                continue;
            }

            if (empty($target)) {
                unset($this->ports[$id]);

                continue;
            }

            if (in_array($published, $publisheds[$protocol])) {
                $context->buildViolation('Ensure all Host Ports are unique per protocol')
                    ->atPath("ports[{$id}][published]")
                    ->addViolation();
            }
            elseif (in_array($published, $this->ports_used[$protocol])) {
                $context->buildViolation('Host Port already used in a different Service')
                    ->atPath("ports[{$id}][published]")
                    ->addViolation();
            }

            if (in_array($target, $targets[$protocol])) {
                $context->buildViolation('Ensure all Container Ports values are unique per protocol')
                    ->atPath("ports[{$id}][target]")
                    ->addViolation();
            }

            $publisheds[$protocol] []= $published;
            $targets[$protocol]    []= $target;
        }
    }

    protected function validateSecrets(ExecutionContextInterface $context)
    {
        $names   = [];
        $targets = [];

        /*
         * Fields:
         *      id
         *      name
         *      data
         * Unique:
         *      name
         */
        foreach ($this->secrets as $id => $secret) {
            $name = trim($secret['name'] ?? '');

            if (empty($name)) {
                $context->buildViolation('Ensure all Secrets have a name')
                    ->atPath("secrets[{$id}][name]")
                    ->addViolation();
            }
            elseif (in_array($name, $names)) {
                $context->buildViolation('Ensure all Secret names are unique')
                    ->atPath("secrets[{$id}][name]")
                    ->addViolation();
            }

            $names   []= $name;
            $targets []= $name;
        }

        /*
         * Fields:
         *      id (checkbox to enable/disable)
         *      name
         *      target
         * Unique:
         *      target
         */
        foreach ($this->secrets_granted as $key => $secret) {
            $id     = trim($secret['id'] ?? '');
            $name   = trim($secret['name'] ?? '');
            $target = trim($secret['target'] ?? '');

            if (empty($id)) {
                unset($this->secrets_granted[$key]);

                continue;
            }

            if (empty($name)) {
                $context->buildViolation('Ensure all Granted Secrets have a name')
                    ->atPath("secrets_granted[{$id}][name]")
                    ->addViolation();
            }

            if (empty($target)) {
                $context->buildViolation('Ensure all Granted Secrets have a filename')
                    ->atPath("secrets_granted[{$id}][target]")
                    ->addViolation();
            }
            elseif (in_array($target, $targets)) {
                $context->buildViolation('Ensure all Granted Secret filenames are unique')
                    ->atPath("secrets_granted[{$id}][target]")
                    ->addViolation();
            }

            $targets []= $target;
        }
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
}
