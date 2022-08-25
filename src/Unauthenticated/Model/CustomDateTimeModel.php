<?php

namespace App\Unauthenticated\Model;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Custom Date Time
 */
class CustomDateTimeModel extends DateTime implements JsonSerializable
{


    /**
     * Constructor for initializing the date time format
     *
     * @param string            $datetime date time
     * @param DateTimeZone|null $timezone timezone
     *
     * @throws InvalidArgumentException on wrong datetime format
     */
    public function __construct($datetime = 'now', DateTimeZone $timezone = null)
    {
        try {
            parent::__construct($datetime, $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Wrong datetime format");
        }
    }


    /**
     * Parse the time
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->format("Y-m-d H:i:s.v");
        // 2021-09-01 10:22:00.983
    }


    /**
     * Parse for database insertion
     *
     * @return string
     */
    public function toDatabase(): string
    {
        // TODO: this was not tested yet.
        $dateStr = $this->format("'Y-m-dTH:i:s.v'");
        return "CONVERT(datetime, $dateStr, 126)";
        // exapmle '2006-04-25T15:50:59.000'
    }


}
