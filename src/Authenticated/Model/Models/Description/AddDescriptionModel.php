<?php

namespace App\Authenticated\Model\Models\Description;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use Psr\Log\LoggerInterface;

/**
 * AddDescriptionModel Class
 */
class AddDescriptionModel extends Model
{

    /**
     * Variable for the mediaId
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
     * Add a description to an object (folder, image, video)
     *
     * @param int                      $userId         user that wrote the description
     * @param string                   $content        text that will be added as the description, written by the user
     * @param int                      $folderId       folder description will be added too, or that contains the media referenced
     * @param MediaReferenceModel|null $mediaReference is null, when the description is being set for the folder
     *
     * @return int
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user is not allowed to edit the folder
     */
    public function addDescription(int $userId, string $content, int $folderId, ?MediaReferenceModel $mediaReference): int
    {
        // test for rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertEdit($userId, $folderId);
        // check if description should get overwritten
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement(self::generateSqlGetDescriptionId($folderId, $mediaReference));
        if (count($results) > 0) {
            // set old description to not visible
            $database->executePreparedStatement(self::generateSqlRemoveDescription($results[0]->id));
        }
        // execute
        $sqlCommandDescription = self::generateSqlAddDescription($userId, $content, $folderId, $mediaReference);
        $id = (int) $database->executePreparedStatement($sqlCommandDescription, true);
        // activity
        if (isset($mediaReference) === true) {
            ActivityModel::insertAddMediaDescriptionActivity($database, $userId, $folderId, $mediaReference);
        } else {
            ActivityModel::insertAddFolderDescriptionActivity($database, $userId, $folderId);
        }
        $database->close();
        return $id;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int                      $userId   user that wrote the description
     * @param string                   $content  text that will be added as the description, written by the user
     * @param int                      $folderId folder description will be added too, or that contains the media referenced
     * @param MediaReferenceModel|null $mediaRef is null, when the description is being set for the folder
     *
     * @return QueryBuilderInsert
     */
    private static function generateSqlAddDescription(int $userId, string $content, int $folderId, ?MediaReferenceModel $mediaRef): QueryBuilderInsert
    {
        // build query
        $queryBuilder = new QueryBuilderInsert();
        $queryBuilder->insert("web_lb_description");
        $queryBuilder->insertValueInt("user_id", $userId);
        $queryBuilder->insertValueStr("description", $content);
        $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
        $queryBuilder->insertValueBool("visible", true);
        $queryBuilder->insertValueInt("folder_id", $folderId);
        if (isset($mediaRef) === true) {
            $queryBuilder->insertValueStr("object_type", "M");
            $queryBuilder->insertValueStr("source", $mediaRef->source);
            $queryBuilder->insertValueInt("object_id", $mediaRef->id);
        } else {
            $queryBuilder->insertValueStr("object_type", "F");
        }
        $queryBuilder->output("id");
        return $queryBuilder;
    }


    /**
     * Generates the sql query to get description id of a folder or media
     *
     * @param int                      $folderId       folder to get description id from, or media is in
     * @param MediaReferenceModel|null $mediaReference when null description id of folder will be returned, otherwise of this media
     *
     * @return QueryBuilderSelect sql query
     */
    private static function generateSqlGetDescriptionId(int $folderId, ?MediaReferenceModel $mediaReference): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("id");
        $queryBuilder->from("web_lb_description");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        if (isset($mediaReference) === true) {
            $queryBuilder->andWhereEqualsInt("object_id", $mediaReference->id);
            $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        } else {
            $queryBuilder->andWhereIsNull("object_id");
            $queryBuilder->andWhereIsNull("source");
        }
        return $queryBuilder;
    }


    /**
     * Update description to make in invisible, don't delete to provide undo functionality
     *
     * @param int $descriptionId id of the description to remove
     *
     * @return QueryBuilderUpdate sql command
     */
    private static function generateSqlRemoveDescription(int $descriptionId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_description");
        $queryBuilder->addSetFunc("updated", "SYSDATETIME()");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("id", $descriptionId);
        return $queryBuilder;
    }


}
