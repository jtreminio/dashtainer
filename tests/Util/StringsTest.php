<?php

namespace Dashtainer\Tests\Util;

use Dashtainer\Util\Strings;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StringsTest extends KernelTestCase
{
    public function testRemoveExtraLineBreaks()
    {
        $data = <<<'EOD'
line 1

    line 2
        line 3
    
    
    line 4
    
line 5

EOD;

        $expected = <<<'EOD'
line 1
    line 2
        line 3
    line 4
line 5

EOD;

        $this->assertEquals($expected, Strings::removeExtraLineBreaks($data));
    }

    public function testHostname()
    {
        $data     = '`!@#$%^&*()-=_AAbb123;"';
        $expected = '--------------AAbb123--';

        $this->assertEquals($expected, Strings::hostname($data));
    }

    public function testFilename()
    {
        $data     = '`!@#$%^&*()-=_AAbb123_.AA;"';
        $expected = '-_AAbb123_.AA';

        $this->assertEquals($expected, Strings::filename($data));
    }
}
