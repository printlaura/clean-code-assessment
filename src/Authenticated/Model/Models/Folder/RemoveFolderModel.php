<?php

namespace App\Authenticated\Model\Models\Folder;

use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Visiting\Model\Models\Folder\GetAllFolderInfosModel;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Folder RemoveFolderModel
 */
class RemoveFolderModel extends Model
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
     * Remove a folder
     *
     * @param int $userId   user that removes folder for their view, no rights necessary
     * @param int $folderId folder ID
     *
     * @throws UnexpectedValueException when query doesn't return exactly one row, while getting folder name
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user can't view folder
     *
     * @return string name of the removed folder
     */
    public function removeFolder(int $userId, int $folderId): string
    {
        // test for rights
        $getFolderRights = new GetFolderRightsModel($this->logger);
        $getFolderRights->assertView($userId, $folderId);
        // build get folderName
        $sqlName = new QueryBuilderSelect();
        $sqlName->select("folderName");
        // TODO: Should this be 'name' instead of 'folderName'? no column name is "folderName" in db
        $sqlName->from("web_lb_folder");
        $sqlName->andWhereEqualsInt("id", $folderId);
        // build remove
        $sqlRemove = new QueryBuilderUpdate();
        $sqlRemove->update("web_lb_folder_user");
        $sqlRemove->addSetStr("visible", "N");
        $sqlRemove->andWhereEqualsInt("folder_id", $folderId);
        $sqlRemove->andWhereEqualsInt("user_id", $userId);
        // execute
        $database   = new Database($this->logger);
        $folderName = GetAllFolderInfosModel::getFolderName($database, $folderId);
        $database->executePreparedStatement($sqlRemove);
        ActivityModel::insertRemoveFolderActivity($database, $userId, $folderId, $folderName);
        $database->close();
        return $folderName;
    }


}
