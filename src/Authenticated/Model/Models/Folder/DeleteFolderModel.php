<?php

namespace App\Authenticated\Model\Models\Folder;

use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Visiting\Model\Models\Folder\GetAllFolderInfosModel;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Folder Delete
 */
class DeleteFolderModel extends Model
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
     * Delete a folder
     *
     * @param int $userId   user that deletes folder, must be owner
     * @param int $folderId folder that will be deleted
     *
     * @return string name of the deleted folder
     * @throws UnexpectedValueException multiple or no names for folder id
     * @throws UserMissingRightsException user must be the folder owner, to execute this action
     * @throws UserUnknownException
     */
    public function deleteFolder(int $userId, int $folderId): string
    {
        $database   = new Database($this->logger);
        $folderName = GetAllFolderInfosModel::getFolderName($database, $folderId);
        // test if user has the rights to delete the folder
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertOwner($userId, $folderId);

        $database->executePreparedStatement(self::generateSqlRemove($folderId));
        $database->executePreparedStatement(self::generateSqlRemoveMedia($folderId));
        $database->executePreparedStatement(self::generateSqlRemoveUser($folderId));
        $database->executePreparedStatement(self::generateSqlRemoveComment($folderId));
        $database->executePreparedStatement(self::generateSqlRemoveDescription($folderId));
        ActivityModel::insertDeleteFolderActivity($database, $userId, $folderId, $folderName);
        $database->close();
        return $folderName;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int $folderId folder that will be removed
     *
     * @return QueryBuilderUpdate
     */
    private static function generateSqlRemove(int $folderId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_folder");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("id", $folderId);
        return $queryBuilder;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int $folderId folder which media will be removed
     *
     * @return QueryBuilderUpdate
     */
    private static function generateSqlRemoveMedia(int $folderId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_folder_media");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        return $queryBuilder;
    }
    

    /**
     * Manage the generation of the SQL
     *
     * @param int $folderId folder where all users right will be removed
     *
     * @return QueryBuilderUpdate
     */
    private static function generateSqlRemoveUser(int $folderId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_folder_user");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        return $queryBuilder;
    }
    

    /**
     * Manage the generation of the SQL
     *
     * @param int $folderId folder where all comments will be removed
     *
     * @return QueryBuilderUpdate
     */
    private static function generateSqlRemoveComment(int $folderId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_comment");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        return $queryBuilder;
    }
    

    /**
     * Manage the generation of the SQL
     *
     * @param int $folderId folder where description will be removed
     *
     * @return QueryBuilderUpdate
     */
    private static function generateSqlRemoveDescription(int $folderId): QueryBuilderUpdate
    {
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_description");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        return $queryBuilder;
    }


}
