<?php

namespace App\Authenticated\Model\Models\User;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserMissingRightsException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * GetFolderRightsModel Class
 */
class GetFolderRightsModel extends Model
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
     * Get the folder rights from the database
     *
     * @param int $userId   user whose rights are requested
     * @param int $folderId folder which rights of the user are requested
     *
     * @return string username
     * @throws UserMissingRightsException when user doesn't have view rights to the folder
     */
    public function getFolderRights(int $userId, int $folderId): string
    {
        // test with folderId
        $sqlCommand = self::getSqlFolderUser($folderId, $userId);
        try {
            $database      = new Database($this->logger);
            $resultsRights = $database->queryPreparedStatement($sqlCommand, 1);
            $database->close();
        } catch (UnexpectedValueException $exception) {
            // user can't view
            throw new UserMissingRightsException("view", "none");
        }
        $resultsRights = $resultsRights[0];
        
        if ($resultsRights->owner === 'Y') {
            return "owner";
        } else if ($resultsRights->visitor === 'E') {
            return "edit";
        } else if ($resultsRights->visitor === 'V') {
            return "view";
        }
        return "view";
    }


    /**
     * Throws exception when user is not owner
     *
     * @param int $userId   user that will be checked for ownership
     * @param int $folderId folder which rights will be checked
     *
     * @return void
     * @throws UserMissingRightsException
     */
    public function assertOwner(int $userId, int $folderId)
    {
        $rights = $this->getFolderRights($userId, $folderId);
        if ($rights === 'owner') {
            return;
        }
        throw new UserMissingRightsException("owner", $rights);
    }


    /**
     * Throws exception when user is not allowed to view the folder
     *
     * @param int $userId   user that will be checked for viewing rights
     * @param int $folderId folder which rights will be checked
     *
     * @return void
     * @throws UserMissingRightsException
     */
    public function assertView(int $userId, int $folderId)
    {
        $this->getFolderRights($userId, $folderId);
    }


    /**
     * Throws exception when user does not have edit rights or is not the owner -> no view, view
     *
     * @param int $userId   user that will be checked for edit rights
     * @param int $folderId folder which rights will be checked
     *
     * @return void
     * @throws UserMissingRightsException
     */
    public function assertEdit(int $userId, int $folderId)
    {
        $rights = $this->getFolderRights($userId, $folderId);
        if ($rights === 'edit') {
            return;
        }
        if ($rights === 'owner') {
            return;
        }
        throw new UserMissingRightsException("edit", $rights);
    }


    /**
     * Generate SQL query to get owner and visitor details of a folder
     *
     * @param integer $folderId folder ID
     * @param integer $userId   user ID
     *
     * @return QueryBuilderSelect
     */
    private static function getSqlFolderUser(int $folderId, int $userId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("owner", "visitor");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsInt("user_id", $userId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        return $queryBuilder;
    }


}
