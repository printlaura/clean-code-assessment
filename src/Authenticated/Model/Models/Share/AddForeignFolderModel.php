<?php

namespace App\Authenticated\Model\Models\Share;

use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\Share\GetFolderIdAndVisitorStatusModel as GFIDVS;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\UserUnknownException;
use Psr\Log\LoggerInterface;

/**
 * Add foreign folder to user folders
 */
class AddForeignFolderModel extends Model
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
     * Add a foreign folder to user folder base
     *
     * @param int    $userId user that adds folder to their view
     * @param string $hash   hash of the folder
     *
     * @return array folder id and rights of the added folder
     * @throws UserUnknownException when user not found
     * @throws FolderUnknownException when folder not found
     */
    public function addForeignFolder(int $userId, string $hash): array
    {

        $database  = new Database($this->logger);
        $checkUser = self::checkUserExists($userId, $database);
        if ($checkUser !== true) {
            throw new UserUnknownException($userId);
        }

        list($folderId, $visitor) = GFIDVS::getFolderIdAndVisitorStatus($hash, $database);
        $userRights = self::checkEntryFolderUser($userId, $folderId, $database);

        $database = new Database($this->logger);
        if (empty($userRights) === true) {
            $queryBuilder = new QueryBuilderInsert();
            $queryBuilder->insert("web_lb_folder_user");
            $queryBuilder->insertValueInt("folder_id", $folderId);
            $queryBuilder->insertValueInt("user_id", $userId);
            $queryBuilder->insertValueBool("visible", true);
            $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
            $queryBuilder->insertValueBool("owner", false);
            $queryBuilder->insertValueStr("visitor", $visitor);
            // execute query
            $database->executePreparedStatement($queryBuilder);
            ActivityModel::insertAddForeignFolderActivity($database, $userId, $folderId);
        } else {
            if ($userRights !== 'V' || $visitor !== 'E') {
                throw new FolderUnknownException($folderId);
            }
            $queryBuilder = new QueryBuilderUpdate();
            $queryBuilder->update("web_lb_folder_user");
            $queryBuilder->addSetStr("visitor", "E");
            $queryBuilder->addSetFunc("updated", "SYSDATETIME()");
            $queryBuilder->andWhereEqualsBool("visible", true);
            $queryBuilder->andWhereEqualsInt("user_id", $userId);
            $queryBuilder->andWhereEqualsInt("folder_id", $folderId);

            // execute query
            $database->executePreparedStatement($queryBuilder);


            ActivityModel::insertUpdateForeignFolderActivity($database, $userId, $folderId);
        }
        $database->close();
        if ($userRights === 'O') {
            $visitor = "owner";
        }

        return [
            "folderid" => intval($folderId),
            "visitor" => $visitor
        ];
    }


    /**
     * Check if exists an entry in the web_lb_folder_user table, to avoid duplicates
     *
     * @param int      $userId   user that will be checked for folder
     * @param int      $folderId folder ID
     * @param Database $database database resource
     *
     * @return string
     */
    private static function checkEntryFolderUser(int $userId, int $folderId, Database $database): string
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("id, visitor, owner");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsInt("user_id", $userId);
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);

        $resultFolder = $database->queryPreparedStatement($queryBuilder);
        $value        = null;
        if (count($resultFolder) > 0 || empty($resultFolder) === false) {
            if (empty($resultFolder[0]->visitor) !== true) {
                $value = $resultFolder[0]->visitor;
            }
            if (isset($resultFolder[0]->owner) === true && $resultFolder[0]->owner === 'Y') {
                $value = 'O';
            }
        }
        return utf8_encode($value);
    }


    /**
     * Check if exists an entry in the web_lb_folder_user table
     *
     * @param int      $userId   will be tested for referencing an existing account
     * @param Database $database database resource ID
     *
     * @return bool
     */
    private static function checkUserExists(int $userId, Database $database): bool
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("id, aktiv");
        $queryBuilder->from("vt_webzugriff_erw");
        $queryBuilder->andWhereEqualsInt("id", $userId);
        $queryBuilder->andWhereEqualsInt("aktiv", 1);

        $resultFolder = $database->queryPreparedStatement($queryBuilder);
        return (count($resultFolder) > 0 || empty($resultFolder) === false);
    }


}
