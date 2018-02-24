<?php

namespace PodYardBundle\Tests\Entity;

use PodYardBundle\Util\HydratorTrait;

class MockEntity
{
    use HydratorTrait;

    public $publicProperty1;
    public $publicProperty2;
    public $publicProperty3;

    protected $protectedProperty1;
    protected $protectedProperty2;
    protected $protectedProperty3;

    private $privateProperty1;
    private $privateProperty2;
    private $privateProperty3;

    public $publicDanglingProperty;

    protected $protectedDanglingProperty;

    public function getPublicProperty1()
    {
        return $this->publicProperty1;
    }

    public function setPublicProperty1($publicProperty1): void
    {
        $this->publicProperty1 = $publicProperty1;
    }

    public function getPublicProperty2()
    {
        return $this->publicProperty2;
    }

    public function setPublicProperty2($publicProperty2): void
    {
        $this->publicProperty2 = $publicProperty2;
    }

    public function getPublicProperty3()
    {
        return $this->publicProperty3;
    }

    public function setPublicProperty3($publicProperty3): void
    {
        $this->publicProperty3 = $publicProperty3;
    }

    public function getProtectedProperty1()
    {
        return $this->protectedProperty1;
    }

    public function setProtectedProperty1($protectedProperty1): void
    {
        $this->protectedProperty1 = $protectedProperty1;
    }

    public function getProtectedProperty2()
    {
        return $this->protectedProperty2;
    }

    public function setProtectedProperty2($protectedProperty2): void
    {
        $this->protectedProperty2 = $protectedProperty2;
    }

    public function getProtectedProperty3()
    {
        return $this->protectedProperty3;
    }

    public function setProtectedProperty3($protectedProperty3): void
    {
        $this->protectedProperty3 = $protectedProperty3;
    }

    public function getPrivateProperty1()
    {
        return $this->privateProperty1;
    }

    public function setPrivateProperty1($privateProperty1): void
    {
        $this->privateProperty1 = $privateProperty1;
    }

    public function getPrivateProperty2()
    {
        return $this->privateProperty2;
    }

    public function setPrivateProperty2($privateProperty2): void
    {
        $this->privateProperty2 = $privateProperty2;
    }

    public function getPrivateProperty3()
    {
        return $this->privateProperty3;
    }

    public function setPrivateProperty3($privateProperty3): void
    {
        $this->privateProperty3 = $privateProperty3;
    }
}
