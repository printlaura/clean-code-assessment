<?php

namespace App\Visiting\Exceptions;

use Exception;

/**
 * Thrown when folder is unknown
 */
class FolderUnknownException extends Exception
{


    /**
     * Throws folder unknown exception
     *
     * @param int $folderId id that is unknown
     */
    public function __construct(int $folderId)
    {
        parent::__construct("Folder '$folderId' not found.", 404);
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
