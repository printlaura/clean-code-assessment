<?php

namespace App\Unauthenticated\Exceptions;

use Exception;

/**
 * Thrown when an unknown source is being used.
 */
class MediaSourceUnknownException extends Exception
{


    /**
     * Throws source unknown exception
     *
     * @param string $source source that is unknown
     */
    public function __construct(string $source)
    {
        parent::__construct("Source '$source' not found or unknown.", 404);
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
