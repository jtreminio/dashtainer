<?php

namespace PodYardBundle\Util;

abstract class HydratableAbstract implements HydratableInterface
{
    private $accessibleProperties = [];
    private $accessibleMethods    = [];

    public function fromArray(array $data)
    {
        foreach ($data as $property => $value) {
            $setter = $this->methodizeName('set', $property);

            if (in_array($setter, $this->getMethods())) {
                call_user_func([$this, $setter], $value);

                continue;
            }

            if (in_array($property, $this->getProperties())) {
                $this->{$property} = $value;
            }
        }
    }

    public function toArray() : array
    {
        $data = [];

        foreach ($this->getProperties() as $property) {
            $getter = $this->methodizeName('get', $property);

            if (in_array($getter, $this->getMethods())) {
                $data[$property] = call_user_func([$this, $getter]);

                continue;
            }

            $data[$property] = $this->{$property};
        }

        return $data;
    }

    private function getProperties() : array
    {
        if (!empty($this->accessibleProperties)) {
            return $this->accessibleProperties;
        }

        $reflect = new \ReflectionClass($this);

        // Only work with public and protected properties
        $reflectedProperties = $reflect->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
        );

        foreach ($reflectedProperties as $property) {
            $this->accessibleProperties[] = $property->getName();
        }

        return $this->accessibleProperties;
    }

    private function getMethods() : array
    {
        if (!empty($this->accessibleMethods)) {
            return $this->accessibleMethods;
        }

        $reflect = new \ReflectionClass($this);

        // Only work with public and protected methods
        $reflectedMethods = $reflect->getMethods(
            \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
        );

        foreach ($reflectedMethods as $method) {
            $this->accessibleMethods[] = $method->getName();
        }

        return $this->accessibleMethods;
    }

    /*
     * Turns `property_name` into ${prepend}PropertyName
     */
    private function methodizeName(string $prepend, string $method) : string
    {
        return $prepend
            . str_replace(' ', '', ucwords(str_replace('_', ' ', $method)));
    }
}
