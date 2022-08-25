<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\Model;
use InvalidArgumentException;

/**
 * Preview class
 */
class PreviewModel extends Model
{

    /**
     * Variable for the id
     *
     * @var integer
     */
    public int $id;

    /**
     * Variable for the mediaThumb
     *
     * @var string
     */
    public string $mediaThumb;

    /**
     * Variable for the mediaSrc
     *
     * @var string
     */
    public string $mediaSrc;

    /**
     * Variable for the type
     *
     * @var string
     */
    public string $type;

    /**
     * Variable for the source
     *
     * @var string
     */
    public string $source;

    /**
     * Variable for the position
     *
     * @var integer
     */
    public int $position;


    /**
     * Constructor define the preview model
     *
     * @param MediaReferenceModel $mediaReference unique reference to a media object, must contain type
     * @param string              $mediaThumb     link to the media thumbnail
     * @param string              $mediaSrc       link to the media source
     * @param int                 $position       position ot the thumbnail
     *
     * @throws InvalidArgumentException if $type is not 'image' or 'video'
     */
    public function __construct(MediaReferenceModel $mediaReference, string $mediaThumb, string $mediaSrc, int $position)
    {
        $this->id     = $mediaReference->id;
        $this->source = $mediaReference->source;
        $mediaReference->assertType();
        $this->type = $mediaReference->type;

        $this->mediaThumb = $mediaThumb;
        $this->mediaSrc   = $mediaSrc;
        $this->position   = $position;
    }


}
