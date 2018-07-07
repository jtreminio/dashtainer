<?php

namespace Dashtainer\Tests\Util;

use Dashtainer\Util\DateTime;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DateTimeTest extends KernelTestCase
{
    public function testToDateTimeReturnsNullOnNullValue()
    {
        $date = null;

        $this->assertNull(DateTime::toDateTime($date));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testToDateTimeThrowsExceptionOnEmptyNonNullValue()
    {
        $date = false;

        $this->assertNull(DateTime::toDateTime($date));
    }

    public function testToDateAcceptsTimestamp()
    {
        $date     = 1520886516;
        $timezone = 'UTC';

        $result = DateTime::toDateTime($date, $timezone);

        $formatted = $result->format('m/d/Y');

        $this->assertEquals('03/12/2018', $formatted);
    }

    public function testToDateAcceptsString()
    {
        $date     = '03/12/2018';
        $timezone = 'UTC';

        $result = DateTime::toDateTime($date, $timezone);

        $formatted = $result->format('m/d/Y');

        $this->assertEquals('03/12/2018', $formatted);
    }

    public function testToDateUpdatesTimezone()
    {
        $date     = new \DateTime(
            '03/12/2018 08:00:00 am',
            new \DateTimeZone('GMT')
        );
        $timezone = 'UTC';

        $result = DateTime::toDateTime($date, $timezone);

        $formatted = $result->format('m/d/Y H:i:s a');

        $this->assertEquals('03/12/2018 08:00:00 am', $formatted);
    }
}
