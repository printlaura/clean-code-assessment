<?php

namespace App\Unauthenticated\Exceptions;

use Exception;
use Throwable;

/**
 * No added functionality just another name
 */
class RequestInValidException extends Exception
{


    /**
     * Constructor without any changes, just calls parent
     *
     * @param string         $message  Exception message that will get displayed
     * @param int            $code     StatusCode, default=500
     * @param Throwable|null $previous previous exception
     */
    public function __construct(string $message, int $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
