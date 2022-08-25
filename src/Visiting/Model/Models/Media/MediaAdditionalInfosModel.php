<?php

namespace App\Visiting\Model\Models\Media;

use App\Unauthenticated\Model\Model;

/**
 * MediaAdditionalInfos class
 */
class MediaAdditionalInfosModel extends Model
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
     * Variable for the commentsCount
     *
     * @var integer
     */
    public int $commentscount;
    
    /**
     * Variable for the notice
     *
     * @var string
     */
    public string $notice;


    /**
     * Constructor define the MediaAdditionalInfos model
     *
     * @param int    $mediaid       media ID
     * @param string $source        link to the media source
     * @param int    $commentsCount comment count for the media
     * @param string $notice        user notice
     */
    public function __construct(
        int     $mediaid,
        string  $source,
        int     $commentsCount,
        string  $notice
    ) {
        $this->mediaid       = $mediaid;
        $this->source        = $source;
        $this->commentscount = $commentsCount;
        $this->notice        = $notice;
    }


}
