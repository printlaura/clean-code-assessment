<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\Model;

/**
 * MediaPrevNext class
 */
class MediaPrevNextModel extends Model
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
     * @param int    $mediaid         media ID
     * @param string $source          link to the media source
     * @param int    $prevMediaId     previous media ID
     * @param string $prevMediaSource the media source of the previous media e.g. sp, st, pond5 or age
     * @param int    $nextMediaId     next media ID
     * @param string $nextMediaSource the media source of the next media e.g. sp, st, pond5 or age
     */
    public function __construct(
        int    $mediaid,
        string $source,
        int    $prevMediaId,
        string $prevMediaSource,
        int    $nextMediaId,
        string $nextMediaSource
    ) {
        $this->mediaid         = $mediaid;
        $this->source          = $source;
        $this->prevmediaid     = $prevMediaId;
        $this->prevmediasource = $prevMediaSource;
        $this->nextmediaid     = $nextMediaId;
        $this->nextmediasource = $nextMediaSource;
    }


}
