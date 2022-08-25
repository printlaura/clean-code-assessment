<?php

namespace App\Visiting\Exceptions;

use Exception;

/**
 * Thrown when user is unknown
 */
class UserUnknownException extends Exception
{


    /**
     * Throws user unknown exception
     *
     * @param int $userId id that is unknown
     */
    public function __construct(int $userId)
    {
        parent::__construct("User '$userId' not found.", 404);
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
