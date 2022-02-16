<?php

namespace SolutionOne;

use DateTime;
use DateTimeZone;

class BasicController
{

    /**
     * @param string $string
     * @return DateTime
     */
    public function convertStringToDate(string $string, string $timezone) : DateTime
    {
        $timezone = new DateTimeZone($timezone);
        return DateTime::createFromFormat('d/F/Y:H:i:s', $string, $timezone);
    }
}