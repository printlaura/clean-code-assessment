<?php

namespace App\Authenticated\Model\Models\Folder;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Activities\ActivityModel;
use Psr\Log\LoggerInterface;

/**
 * CreateFolderModel Class
 */
class CreateFolderModel extends Model
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
     * Create a folder for a user, manage the sql query generating
     *
     * @param int    $userId     user that creates the folder, will also be the owner
     * @param string $folderName name of the new folder
     *
     * @throws UserUnknownException
     *
     * @return int generated folder id of the new created folder
     */
    public function createFolder(int $userId, string $folderName): int
    {
        $database = new Database($this->logger);
        // folder
        $sqlCommandFolder = self::getSqlCommandFolder($folderName);
        $folderId         = (int) $database->executePreparedStatement($sqlCommandFolder, true);
        // folder_user
        $sqlCommandFolderUser = self::getSqlCommandFolderUser($folderId, $userId);
        $database->executePreparedStatement($sqlCommandFolderUser);
        // activity
        ActivityModel::insertCreateFolderActivity($database, $userId, $folderId, $folderName);
        $database->close();
        return $folderId;
    }


    /**
     * Generate insert sql command for the folder table
     *
     * @param string $folderName name of the new folder
     *
     * @return QueryBuilderInsert
     */
    private static function getSqlCommandFolder(string $folderName): QueryBuilderInsert
    {
        $queryBuilder = new QueryBuilderInsert();
        $queryBuilder->insert("web_lb_folder");
        $queryBuilder->insertValueStr("name", "$folderName");
        $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
        $queryBuilder->insertValueBool("visible", true);
        $queryBuilder->insertValueStr("viewhash", self::generateHash(20));
        $queryBuilder->insertValueStr("edithash", self::generateHash(20));
        $queryBuilder->output("id");
        return $queryBuilder;
    }
    
    
    /**
     * Generate insert sql command for the folder user table
     *
     * @param int $folderId name of the new folder
     * @param int $userId   user that creates the folder
     *
     * @return QueryBuilderInsert
     */
    private static function getSqlCommandFolderUser(int $folderId, int $userId): QueryBuilderInsert
    {
        $queryBuilder = new QueryBuilderInsert();
        $queryBuilder->insert("web_lb_folder_user");
        $queryBuilder->insertValueInt("folder_id", $folderId);
        $queryBuilder->insertValueInt("user_id", $userId);
        $queryBuilder->insertValueBool("visible", true);
        $queryBuilder->insertValueBool("owner", true);
        $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
        return $queryBuilder;
    }


    /**
     * Generates a string with using random chars A-Z,a-z,0-1
     *
     * @param int $length amount of characters in the generated string
     *
     * @return string
     */
    public static function generateHash(int $length): string
    {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $str     = '';
        $count   = strlen($charset);
        for ($i = 0; $i < $length; $i++) {
            $str .= $charset[mt_rand(0, ($count - 1))];
        }
        return $str;
    }


}
