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
 * Folder RenameFolderModel
 */
class RenameFolderModel extends Model
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
     * Rename a folder
     *
     * @param int    $userId   user that renames the folder, edit rights necessary
     * @param int    $folderId folder ID
     * @param string $name     name of the folder
     *
     * @throws UnexpectedValueException when query doesn't return exactly one row, while getting folder name
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user is not allowed to edit
     *
     * @return null
     */
    public function renameFolder(int $userId, int $folderId, string $name)
    {
        // test for rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertEdit($userId, $folderId);
        // build
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_folder");
        $queryBuilder->addSetStr("name", $name);
        $queryBuilder->andWhereEqualsInt("id", $folderId);
        // execute
        $database           = new Database($this->logger);
        $previousFolderName = GetAllFolderInfosModel::getFolderName($database, $folderId);
        $database->executePreparedStatement($queryBuilder);
        ActivityModel::insertRenameFolderActivity($database, $userId, $folderId, $previousFolderName, $name);
        $database->close();
        return;
    }


}
