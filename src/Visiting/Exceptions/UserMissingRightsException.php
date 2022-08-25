<?php

namespace App\Visiting\Exceptions;

use Exception;

/**
 * Thrown when user is missing rights
 */
class UserMissingRightsException extends Exception
{


    /**
     * Throws user missing rights exception
     *
     * @param string $expectedRights rights the user needed to perform action
     * @param string $currentRights  rights the user has instead
     */
    public function __construct(string $expectedRights, string $currentRights)
    {
        parent::__construct("User is not allowed to perform this action. User has $currentRights right but needs $expectedRights.", 405);
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
