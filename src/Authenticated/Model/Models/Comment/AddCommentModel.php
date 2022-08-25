<?php

namespace App\Authenticated\Model\Models\Comment;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * AddCommentModel Class
 */
class AddCommentModel extends Model
{

    /**
     * Variable for the logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * Constructor for initializing the logger
     *
     * @param LoggerInterface $logger Logger-Variable
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Add a comment to a media or folder
     *
     * @param integer                  $userId         user ID
     * @param string                   $content        text of the comment
     * @param string                   $objecttype     object type "folder" or "media"
     * @param MediaReferenceModel|null $mediaReference when null comment will be added to folder otherwise to this media
     * @param int                      $folderId       comment will be added to, or media is in
     *
     * @return int
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user is not allowed to view folder
     */
    public function addComment(int $userId, string $content, string $objecttype, ?MediaReferenceModel $mediaReference, int $folderId): int
    {
        // test arguments
        if ($objecttype === "media") {
            if (isset($mediaReference) === false) {
                throw new InvalidArgumentException("media must contain mediaReference");
            }
        } else if ($objecttype === "folder") {
            if (isset($folderId) === false) {
                throw new InvalidArgumentException("folder must contain folderId");
            }
        } else {
            throw new InvalidArgumentException("objecttype $objecttype unexpected value, must be either 'media' or 'folder'");
        }
        // test for rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertView($userId, $folderId);
        // build query
        $queryBuilder = new QueryBuilderInsert();
        $queryBuilder->insert("web_lb_comment");
        $queryBuilder->insertValueInt("user_id", $userId);
        $queryBuilder->insertValueStr("comment", $content);
        $queryBuilder->insertValueInt("folder_id", $folderId);
        $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
        if ($objecttype === "folder") {
            $queryBuilder->insertValueStr("object_type", "F");
            $queryBuilder->insertValueInt("object_id", $folderId);
        } else {
            $queryBuilder->insertValueStr("object_type", "M");
            $queryBuilder->insertValueInt("object_id", $mediaReference->id);
            $queryBuilder->insertValueStr("source", $mediaReference->source);
        }
        $queryBuilder->output("id");
        // execute query
        $database = new Database($this->logger);
        $id       = (int) $database->executePreparedStatement($queryBuilder, true);
        // activity
        if (isset($mediaReference) === true) {
            ActivityModel::insertAddMediaCommentActivity($database, $userId, $folderId, $id);
        } else {
            ActivityModel::insertAddFolderCommentActivity($database, $userId, $folderId, $id);
        }
        $database->close();
        return $id;
    }


}
