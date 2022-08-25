<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\Model;

/**
 * Class for getting the previous and next media id
 */
class GetPrevNextMediaModel extends Model
{


    /**
     * Create a new model instance.
     */
    public function __construct()
    {
    }


    /**
     * Get all media in a folder from the database.
     *
     * @param MediaReferenceModel $mediaReference media that is being viewed
     *
     * @return MediaPrevNextModel
     */
    public function get(MediaReferenceModel $mediaReference): MediaPrevNextModel
    {
        $prevMediaId     = ($mediaReference->id - 1);
        $prevMediaSource = $mediaReference->source;
        $nextMediaId     = ($mediaReference->id + 1);
        $nextMediaSource = $mediaReference->source;

        return new MediaPrevNextModel(
            $mediaReference->id,
            $mediaReference->source,
            $prevMediaId,
            $prevMediaSource,
            $nextMediaId,
            $nextMediaSource
        );
    }


}
