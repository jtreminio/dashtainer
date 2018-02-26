<?php

namespace DashtainerBundle\Tests\Util;

use DashtainerBundle\Tests\Entity\MockEntity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HydratableTest extends KernelTestCase
{
    public function testToArrayExcludesPrivateGetMethods()
    {
        $entity = new MockEntity();
        $entity->fromArray($this->getEntityData());

        $expected = [
            'publicProperty1'           => 'publicValue1',
            'publicProperty2'           => 'publicValue2',
            'publicProperty3'           => 'publicValue3',
            'protectedProperty1'        => 'protectedValue1',
            'protectedProperty2'        => 'protectedValue2',
            'protectedProperty3'        => 'protectedValue3',
            'publicDanglingProperty'    => null,
            'protectedDanglingProperty' => null,
        ];

        $this->assertEquals($expected, $entity->toArray());
    }

    public function testPublicGetMethodsReturnData()
    {
        $entityData = $this->getEntityData();

        $entity = new MockEntity();
        $entity->setPublicProperty1($entityData['publicProperty1']);
        $entity->setPublicProperty2($entityData['publicProperty2']);
        $entity->setPublicProperty3($entityData['publicProperty3']);

        $expected = [
            'publicProperty1'           => 'publicValue1',
            'publicProperty2'           => 'publicValue2',
            'publicProperty3'           => 'publicValue3',
            'protectedProperty1'        => null,
            'protectedProperty2'        => null,
            'protectedProperty3'        => null,
            'publicDanglingProperty'    => null,
            'protectedDanglingProperty' => null,
        ];

        $this->assertEquals($expected, $entity->toArray());
    }

    public function testProtectedGetMethodsReturnData()
    {
        $entityData = $this->getEntityData();

        $entity = new MockEntity();
        $entity->setProtectedProperty1($entityData['protectedProperty1']);
        $entity->setProtectedProperty2($entityData['protectedProperty2']);
        $entity->setProtectedProperty3($entityData['protectedProperty3']);

        $expected = [
            'publicProperty1'           => null,
            'publicProperty2'           => null,
            'publicProperty3'           => null,
            'protectedProperty1'        => 'protectedValue1',
            'protectedProperty2'        => 'protectedValue2',
            'protectedProperty3'        => 'protectedValue3',
            'publicDanglingProperty'    => null,
            'protectedDanglingProperty' => null,
        ];

        $this->assertEquals($expected, $entity->toArray());
    }

    public function testPrivateGetMethodsDoNotReturnData()
    {
        $entityData = $this->getEntityData();

        $entity = new MockEntity();
        $entity->setPrivateProperty1($entityData['privateProperty1']);
        $entity->setPrivateProperty2($entityData['privateProperty2']);
        $entity->setPrivateProperty3($entityData['privateProperty3']);

        $expected = [
            'publicProperty1'           => null,
            'publicProperty2'           => null,
            'publicProperty3'           => null,
            'protectedProperty1'        => null,
            'protectedProperty2'        => null,
            'protectedProperty3'        => null,
            'publicDanglingProperty'    => null,
            'protectedDanglingProperty' => null,
        ];

        $this->assertEquals($expected, $entity->toArray());

        $this->assertEquals(
            $entityData['privateProperty1'],
            $entity->getPrivateProperty1()
        );

        $this->assertEquals(
            $entityData['privateProperty2'],
            $entity->getPrivateProperty2()
        );

        $this->assertEquals(
            $entityData['privateProperty3'],
            $entity->getPrivateProperty3()
        );
    }

    public function testFromArrayIgnoresUndefinedProperties()
    {
        $entity = new MockEntity();
        $entity->fromArray($this->getEntityDataWithTrash());

        $expected = [
            'publicProperty1'           => 'publicValue1',
            'publicProperty2'           => 'publicValue2',
            'publicProperty3'           => 'publicValue3',
            'protectedProperty1'        => 'protectedValue1',
            'protectedProperty2'        => 'protectedValue2',
            'protectedProperty3'        => 'protectedValue3',
            'publicDanglingProperty'    => null,
            'protectedDanglingProperty' => null,
        ];

        $this->assertEquals($expected, $entity->toArray());
    }

    public function testFromArraySetsPublicOrProtectedValueWithoutSetterMethod()
    {
        $data = [
            'publicDanglingProperty'    => 'publicDanglingValue',
            'protectedDanglingProperty' => 'protectedDanglingValue',
        ];

        $entity = new MockEntity();
        $entity->fromArray($data);

        $expected = [
            'publicProperty1'           => null,
            'publicProperty2'           => null,
            'publicProperty3'           => null,
            'protectedProperty1'        => null,
            'protectedProperty2'        => null,
            'protectedProperty3'        => null,
            'publicDanglingProperty'    => 'publicDanglingValue',
            'protectedDanglingProperty' => 'protectedDanglingValue',
        ];

        $this->assertEquals($expected, $entity->toArray());
    }

    protected function getEntityData() : array
    {
        return [
            'publicProperty1'    => 'publicValue1',
            'publicProperty2'    => 'publicValue2',
            'publicProperty3'    => 'publicValue3',
            'protectedProperty1' => 'protectedValue1',
            'protectedProperty2' => 'protectedValue2',
            'protectedProperty3' => 'protectedValue3',
            'privateProperty1'   => 'privateValue1',
            'privateProperty2'   => 'privateValue2',
            'privateProperty3'   => 'privateValue3',
        ];
    }

    protected function getEntityDataWithTrash() : array
    {
        return [
            'publicProperty1'    => 'publicValue1',
            'publicProperty2'    => 'publicValue2',
            'publicProperty3'    => 'publicValue3',
            'protectedProperty1' => 'protectedValue1',
            'protectedProperty2' => 'protectedValue2',
            'protectedProperty3' => 'protectedValue3',
            'privateProperty1'   => 'privateValue1',
            'privateProperty2'   => 'privateValue2',
            'privateProperty3'   => 'privateValue3',
            'trashProperty1'     => 'trashValue1',
            'trashProperty2'     => 'trashValue2',
            'trashProperty3'     => 'trashValue3',
        ];
    }
}
