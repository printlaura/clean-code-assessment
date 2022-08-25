<?php

namespace App\Visiting\Model\Models\User;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\UserUnknownException;
use UnexpectedValueException;

/**
 * GetUserNameModel Class
 */
class GetUserNameModel extends Model
{

    private Database $database;

    /**
     * Here are usernames being stored, to prevent executing a query every time
     *
     * @var array id=>username
     */
    private array $userIdNameDict = [];


    /**
     * Constructor for initializing the logger
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }


    /**
     * Get the users name from the database for multiple folders
     *
     * @param int[] $folderIds folder reference
     *
     * @return array username
     */
    public function getMultipleOwnerNames(array $folderIds): array
    {
        // build SQL
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("web_lb_folder_user.folder_id AS folderid", "vt_webzugriff_erw.name as ownername");
        $queryBuilder->from("vt_webzugriff_erw, web_lb_folder_user");
        $queryBuilder->andWhereEqualsFunc("web_lb_folder_user.user_id", "vt_webzugriff_erw.id");
        $queryBuilder->andWhereEqualsInt("vt_webzugriff_erw.aktiv", 1);
        $queryBuilder->andWhereEqualsStr("web_lb_folder_user.owner", "Y");
        $queryBuilder->andWhereIsInIntArray("web_lb_folder_user.folder_id", $folderIds);
        // execute Query
        $results = $this->database->queryPreparedStatement($queryBuilder);
        // parse results
        $folderOwnernameDict = [];
        foreach ($results as $result) {
            $folderOwnernameDict[$result->folderid] = utf8_encode($result->ownername);
        }
        return $folderOwnernameDict;
    }


    /**
     * Get the users name from the database
     *
     * @param int $userId user reference
     *
     * @return string username
     * @throws UserUnknownException
     */
    public function getUserName(int $userId): string
    {
        if (isset($this->userIdNameDict[$userId]) === true) {
            // When the username was already retrieved, this saves executing another query
            return $this->userIdNameDict[$userId];
        }
        // build SQL
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("name");
        $queryBuilder->from("vt_webzugriff_erw");
        $queryBuilder->andWhereEqualsInt("aktiv", 1);
        $queryBuilder->andWhereEqualsInt("id", $userId);
        // execute Query
        try {
            $results = $this->database->queryPreparedStatement($queryBuilder, 1);
        } catch (UnexpectedValueException $exception) {
            throw new UserUnknownException($userId);
        }

        $userName = utf8_encode($results[0]->name);
        $this->userIdNameDict[$userId] = $userName;
        return $userName;
    }


    /**
     * Get owner names of their folders from the database
     *
     * @param int $folderId folder reference
     *
     * @return string username
     * @throws FolderUnknownException when no user entry was found in the folder
     */
    public function getOwnerName(int $folderId): string
    {
        // build SQL
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("vt_webzugriff_erw.name");
        $queryBuilder->from("vt_webzugriff_erw, web_lb_folder_user");
        $queryBuilder->andWhereEqualsFunc("web_lb_folder_user.user_id", "vt_webzugriff_erw.id");
        $queryBuilder->andWhereEqualsInt("vt_webzugriff_erw.aktiv", 1);
        $queryBuilder->andWhereEqualsStr("web_lb_folder_user.owner", "Y");
        $queryBuilder->andWhereEqualsInt("web_lb_folder_user.folder_id", $folderId);

        // execute Query
        try {
            $results = $this->database->queryPreparedStatement($queryBuilder, 1);
        } catch (UnexpectedValueException $exception) {
            throw new FolderUnknownException($folderId);
        }

        return utf8_encode($results[0]->name);
    }


}
