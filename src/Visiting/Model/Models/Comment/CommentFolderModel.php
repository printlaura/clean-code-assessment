<?php

namespace App\Visiting\Model\Models\Comment;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\Model;

/**
 * CommentFolder class
 */
class CommentFolderModel extends Model
{
    const DB_NAME = 'F';

    /**
     * Variable for the folderId
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
     * @param int|null            $folderId folder the comment was written on
     * @param string              $writer   user that wrote the comment
     * @param string              $comment  comment text
     * @param CustomDateTimeModel $created  creation date of the comment
     */
    public function __construct(int $folderId, string $writer, string $comment, CustomDateTimeModel $created)
    {
        $this->folderid = $folderId;
        $this->writer   = $writer;
        $this->comment  = utf8_encode($comment);
        $this->created  = $created;
    }


}
