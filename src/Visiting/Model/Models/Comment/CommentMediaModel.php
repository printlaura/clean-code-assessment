<?php

namespace App\Visiting\Model\Models\Comment;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;

/**
 * CommentMedia class
 */
class CommentMediaModel extends Model
{

    /**
     * ID of the media
     *
     * @var integer
     */
    public int $mediaid;

    /**
     * Source of the media
     *
     * @var string
     */
    public string $source;

    /**
     * Folder the media is in
     *
     * @var integer
     */
    public int $folderid;

    /**
     * Username of the comment writer
     *
     * @var string
     */
    public string $writer;

    /**
     * Content of the comment
     *
     * @var string
     */
    public string $comment;

    /**
     * Time and date the comment was written
     *
     * @var CustomDateTimeModel
     */
    public CustomDateTimeModel $created;


    /**
     * Constructor define the comment model
     *
     * @param MediaReferenceModel $mediaReference media the comment is linked to
     * @param string              $writer         user that wrote the comment
     * @param int                 $folderId       folder the media is in
     * @param string              $comment        comment text
     * @param CustomDateTimeModel $created        creation date of the comment
     */
    public function __construct(MediaReferenceModel $mediaReference, string $writer, int $folderId, string $comment, CustomDateTimeModel $created)
    {
        $this->mediaid  = $mediaReference->id;
        $this->source   = $mediaReference->source;
        $this->writer   = $writer;
        $this->folderid = $folderId;
        $this->comment  = $comment;
        $this->created  = $created;
        return null;
    }


}
