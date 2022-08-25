<?php

namespace App\Unauthenticated\Exceptions;

use Exception;

/**
 * Thrown when license is unknown
 */
class LicenseUnknownException extends Exception
{


    /**
     * Throws user unknown exception
     */
    public function __construct()
    {
        parent::__construct("License not found.", 404);
    }


    /**
     * Returns message and code
     *
     * @return string string representation of the exception
     */
    public function __toString()
    {
        return __CLASS__.": [$this->code]: $this->message\n";
    }


}
