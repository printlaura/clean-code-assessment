<?php

namespace App\Unauthenticated\Exceptions;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use Exception;

/**
 * Thrown when folder is unknown
 */
class MediaUnknownException extends Exception
{


    /**
     * Throws media unknown exception
     *
     * @param MediaReferenceModel $mediaReference that is unknown
     */
    public function __construct(MediaReferenceModel $mediaReference)
    {
        parent::__construct("Media $mediaReference not found.", 404);
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
