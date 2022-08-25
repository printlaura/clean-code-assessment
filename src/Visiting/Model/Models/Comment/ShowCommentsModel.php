<?php

namespace App\Visiting\Model\Models\Comment;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Model\Models\User\GetUserNameModel;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * ShowCommentsModel Class
 */
class ShowCommentsModel extends Model
{

    /**
     * Logger reference
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
     * Get all comments from the database
     *
     * @param LoggerInterface          $logger         logger
     * @param int                      $folderId       get comments from folder, or folder where media is in
     * @param MediaReferenceModel|null $mediaReference when null comments of folder will be returned, otherwise media the comments will be returned of
     *
     * @return CommentMediaModel[]
     */
    public static function getAllComments(LoggerInterface $logger, int $folderId, ?MediaReferenceModel $mediaReference = null): array
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "object_id",
            "object_type",
            "folder_id",
            "user_id",
            "comment",
            "source",
            "updated"
        );
        $queryBuilder->from("web_lb_comment");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        if (isset($mediaReference) === true) {
            $queryBuilder->andWhereEqualsStr("object_type", "M");
            $queryBuilder->andWhereEqualsInt("object_id", $mediaReference->id);
            $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        } else {
            $queryBuilder->andWhereEqualsStr("object_type", "F");
            $queryBuilder->andWhereEqualsInt("object_id", $folderId);
        }
        // execute query
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($queryBuilder);
        // parse comments
        $comments    = [];
        $getUserName = new GetUserNameModel($database);
        foreach ($results as $result) {
            try {
                if ($result->object_type === CommentFolderModel::DB_NAME) { // phpcs:ignore
                    $comment = new CommentFolderModel(
                        $result->folder_id,// phpcs:ignore
                        $getUserName->getUserName($result->user_id),// phpcs:ignore
                        $result->comment,
                        new CustomDateTimeModel($result->updated)
                    );
                } else {
                    $comment = new CommentMediaModel(
                        new MediaReferenceModel($result->source, $result->object_id),// phpcs:ignore
                        $getUserName->getUserName($result->user_id),// phpcs:ignore
                        $result->folder_id,// phpcs:ignore
                        $result->comment,
                        new CustomDateTimeModel($result->updated)
                    );
                }
                $comments[] = $comment;
            } catch (Exception $exception) {
                $logger->error("Error when creating comment instance.", [$exception]);
                // skip this comment and don't display it
            }
        }
        $database->close();
        return $comments;
    }


    /**
     * Get the amount of comments that are linked to a folder
     *
     * @param int $folderId folder, which comments will be counted
     *
     * @return int amount of comments
     */
    public function getFolderCommentCount(int $folderId): int
    {
        return $this->getCommentCount($folderId);
    }


    /**
     * Get the amount of comments that are linked to a media
     *
     * @param int                 $folderId       folder the media is in
     * @param MediaReferenceModel $mediaReference media where comments will be counted
     *
     * @return int amount of comments
     */
    public function getMediaCommentCount(int $folderId, MediaReferenceModel $mediaReference): int
    {
        return $this->getCommentCount($folderId, $mediaReference);
    }


    /**
     * Get the amount of comments that are linked to a folder or media
     *
     * @param int                      $folderId       folder, which comments will be counted, or folder the media is in
     * @param MediaReferenceModel|null $mediaReference when null comments of folder will be counted, otherwise media the comments will be counted
     *
     * @return int amount of comments
     */
    private function getCommentCount(int $folderId, ?MediaReferenceModel $mediaReference = null): int
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("COUNT(id) AS amount");
        $queryBuilder->from("web_lb_comment");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        if (isset($mediaReference) === false) {
            $queryBuilder->andWhereEqualsStr("object_type", "F");
            $queryBuilder->andWhereEqualsInt("object_id", $folderId);
        } else {
            $queryBuilder->andWhereEqualsStr("object_type", "M");
            $queryBuilder->andWhereEqualsInt("object_id", $mediaReference->id);
            $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        }
        // execute Query
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder, 1);
        $database->close();
        return (int) $results[0]->amount;
    }


    /**
     * Gets the amount of comments on a folder for multiple folders
     *
     * @param int[] $folderIds
     *
     * @return array dict with folderId as key
     */
    public function getMutlitpleFoldersCommentCount(array $folderIds): array
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("folder_id AS folderid", "COUNT(id) AS amount");
        $queryBuilder->from("web_lb_comment");
        $queryBuilder->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilder->andWhereEqualsStr("object_type", "F");
        $queryBuilder->andWhereIsInIntArray("object_id", $folderIds);
        $queryBuilder->groupBy("folder_id");
        // execute Query
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder);
        $database->close();
        // parse results
        $folderCommentCountDict = [];
        foreach ($results as $result) {
            $folderCommentCountDict[$result->folderid] = $result->amount;
        }
        // set default count to 0
        foreach ($folderIds as $folderId) {
            if (isset($folderCommentCountDict[$folderId]) === false) {
                $folderCommentCountDict[$folderId] = 0;
            }
        }
        return $folderCommentCountDict;
    }


}
