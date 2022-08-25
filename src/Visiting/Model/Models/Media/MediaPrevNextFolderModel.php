<?php

namespace App\Visiting\Model\Models\Media;

use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;

/**
 * MediaPrevNextFolder class
 */
class MediaPrevNextFolderModel extends Model
{

    /**
     * Variable for the id
     *
     * @var integer
     */
    public int $mediaid;
    
    /**
     * Variable for the source
     *
     * @var string
     */
    public string $source;

    /**
     * Variable for the prevMediaId
     *
     * @var integer
     */
    public int $prevmediaid;
    
    /**
     * Variable for the prevMediaSource
     *
     * @var string
     */
    public string $prevmediasource;
        
    /**
     * Variable for the nextMediaId
     *
     * @var integer
     */
    public int $nextmediaid;

    /**
     * Source for the following media
     *
     * @var string
     */
    public string $nextmediasource;


    /**
     * Constructor define the MediaInfo model
     *
     * @param MediaReferenceModel $mediaReference  link to the media
     * @param int                 $prevMediaId     previous media ID
     * @param string              $prevMediaSource the media source of the previous media e.g. sp, st, pond5 or age
     * @param int                 $nextMediaId     next media ID
     * @param string              $nextMediaSource the media source of the next media e.g. sp, st, pond5 or age
     */
    public function __construct(
        MediaReferenceModel $mediaReference,
        int                 $prevMediaId,
        string              $prevMediaSource,
        int                 $nextMediaId,
        string              $nextMediaSource
    ) {
        $this->mediaid         = $mediaReference->id;
        $this->source          = $mediaReference->source;
        $this->prevmediaid     = $prevMediaId;
        $this->prevmediasource = $prevMediaSource;
        $this->nextmediaid     = $nextMediaId;
        $this->nextmediasource = $nextMediaSource;
    }


}
