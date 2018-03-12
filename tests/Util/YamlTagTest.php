<?php

namespace Dashtainer\Tests\Util;

use Dashtainer\Util\YamlTag;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

class YamlTagTest extends KernelTestCase
{
    public function testDoubleQuotes()
    {
        $yaml = Yaml::dump([
            'bar' => 'biz',
            'foo' => YamlTag::doubleQuotes('must be double quoted'),
            'qiz' => [],
        ]);

        $expected = <<<'EOD'
bar: biz
foo: "must be double quoted"
qiz: {  }

EOD;

        $this->assertEquals(
            $expected,
            YamlTag::parse($yaml)
        );
    }

    public function testDoubleQuotesNoSingleQuotes()
    {
        $yaml = <<<'EOD'
bar: biz
foo: __SPECIAL_DBL_QUOTES__must be double quoted__SPECIAL_DBL_QUOTES__
qiz: {  }

EOD;

        $expected = <<<'EOD'
bar: biz
foo: "must be double quoted"
qiz: {  }

EOD;

        $this->assertEquals(
            $expected,
            YamlTag::parse($yaml)
        );
    }

    public function testNilValue()
    {
        $yaml = Yaml::dump([
            'bar' => 'biz',
            'foo' => YamlTag::nilValue(),
            'qiz' => [],
        ]);

        $expected = <<<'EOD'
bar: biz
foo: 
qiz: {  }

EOD;

        $this->assertEquals(
            $expected,
            YamlTag::parse($yaml)
        );
    }

    public function testEmptyHash()
    {
        $yaml = Yaml::dump([
            'bar' => 'biz',
            'foo' => YamlTag::emptyHash(),
            'qiz' => [],
        ]);

        $expected = <<<'EOD'
bar: biz
foo: {  }
qiz: {  }

EOD;

        $this->assertEquals(
            $expected,
            YamlTag::parse($yaml)
        );
    }
}
