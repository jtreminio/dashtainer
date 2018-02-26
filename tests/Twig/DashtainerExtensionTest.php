<?php

namespace DashtainerBundle\Tests\Twig;

use DashtainerBundle\Twig\DashtainerExtension;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DashtainerExtensionTest extends KernelTestCase
{
    /** @var DashtainerExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new DashtainerExtension();
    }

    ## Twig_Filter tests

    /**
     * @dataProvider getPregQuoteStrings
     * @group twig_filter
     */
    public function testPregQuoteString(string $string, string $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->preg_quote($string)
        );
    }

    public function getPregQuoteStrings()
    {
        yield ['$40 for a g3/400', '\$40 for a g3/400'];
    }

    /**
     * @dataProvider getPregQuoteArray
     * @group twig_filter
     */
    public function testPregQuoteArray(array $strings, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->preg_quote($strings)
        );
    }

    public function getPregQuoteArray()
    {
        yield [
            [
                '$40 for a g3/400',
                '$30 for a g3/300',
                '$20 for a g3/200',
            ],
            [
                '\$40 for a g3/400',
                '\$30 for a g3/300',
                '\$20 for a g3/200',
            ]
        ];
    }

    /**
     * @dataProvider getStrReplace
     * @group twig_filter
     */
    public function testStrReplace($subject, $search, $replace, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->str_replace($subject, $search, $replace)
        );
    }

    public function getStrReplace()
    {
        yield ['foobar', 'foo', 'biz', 'bizbar'];
    }

    ## Twig_Function tests
    // @group twig_function

    ## Twig_Test tests

    /**
     * @dataProvider getIsHashTable
     * @group twig_test
     */
    public function testIsHashTable($arr, bool $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->isHashTable($arr)
        );
    }

    public function getIsHashTable()
    {
        yield [['foo' => 'bar'], true];
        yield [['foo'], false];
        yield ['foo', false];
        yield [0, false];
        yield [false, false];
    }

    /**
     * @dataProvider getIsString
     * @group twig_test
     */
    public function testIsString($string, bool $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->is_string($string)
        );
    }

    public function getIsString()
    {
        yield ['string', true];
        yield [[], false];
        yield [0, false];
    }
}
