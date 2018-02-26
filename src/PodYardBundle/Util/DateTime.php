<?php

namespace PodYardBundle\Util;

abstract class DateTime
{
    /**
     * Turns timestamp or string into proper DateTime object
     *
     * Sets default timezone
     *
     * @param string|int|\DateTime $dtCandidate
     * @param string               $tzName
     * @return \DateTime
     */
    public static function toDateTime($dtCandidate, string $tzName = null) : ?\DateTime
    {
        if (is_null($dtCandidate)) {
            return null;
        }

        if (empty($dtCandidate)) {
            throw new \InvalidArgumentException('dtCandidate cannot be empty!');
        }

        $tz = new \DateTimeZone($tzName ?? date_default_timezone_get());

        if (is_numeric($dtCandidate) && (int) $dtCandidate == $dtCandidate) {
            $dt = \DateTime::createFromFormat('u', $dtCandidate);
            $dt->setTimezone($tz);

            return $dt;
        }

        if (!is_a($dtCandidate, 'DateTime')) {
            $dt = new \DateTime($dtCandidate);
            $dt->setTimezone($tz);

            return $dt;
        }

        $dtCandidate->setTimezone($tz);

        return $dtCandidate;
    }
}
