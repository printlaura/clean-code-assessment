<?php

namespace App\Visiting\Exceptions;

use Exception;

/**
 * Thrown when folder is unknown
 */
class HashUnknownException extends Exception
{


    /**
     * Throws hash unknown exception
     *
     * @param string $hash hash that is unknown
     */
    public function __construct(string $hash)
    {
        parent::__construct("Hash '$hash' not found.", 404);
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
