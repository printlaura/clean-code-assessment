<?php

namespace App\Unauthenticated\Exceptions;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use Exception;

/**
 * Thrown when requested media is not present in the folder.
 */
class MediaNotInFolderException extends Exception
{


    /**
     * Throws media not in folder exception
     *
     * @param MediaReferenceModel $mediaReferenceModel
     */
    public function __construct(MediaReferenceModel $mediaReferenceModel)
    {
        parent::__construct("$mediaReferenceModel not found in folder.", 404);
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
