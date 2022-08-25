<?php

namespace App\Visiting\Model\Models\Share;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserUnknownException;
use App\Visiting\Model\Models\User\GetUserNameModel;
use Psr\Log\LoggerInterface;

/**
 * Show folder share class
 */
class ShowFolderShareModel extends Model
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
     * Get the amount of users, the folder is being shared with (excluding the owner). For multiple folders.
     *
     * @param int[] $folderIds
     *
     * @return array dict with folderId as key
     */
    public function getMultipleFoldersShareCount(array $folderIds): array
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("folder_id AS folderid", "COUNT(user_id) AS amount");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsBool("owner", false);
        $queryBuilder->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->groupBy("folder_id");
        // execute query
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder);
        $database->close();
        // parse results
        $folderShareCountDict = [];
        foreach ($results as $result) {
            $folderShareCountDict[$result->folderid] = $result->amount;
        }
        // set default count to 0
        foreach ($folderIds as $folderId) {
            if (isset($folderShareCountDict[$folderId]) === false) {
                $folderShareCountDict[$folderId] = 0;
            }
        }
        return $folderShareCountDict;
    }


    /**
     * Get shared folder
     *
     * @param int $folderId folder ID
     *
     * @return array
     */
    public function getSharesFolder(int $folderId): array
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("user_id AS userid", "visitor");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("owner", false);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // $queryBuilder->andWhereInFunc("folder_id", "(SELECT folder_id FROM folder_user WHERE (owner='Y' OR visitor='E') AND visible='Y' AND user_id=$userId)");
        $queryBuilder->andWhereInFunc("folder_id", "(SELECT folder_id FROM web_lb_folder_user WHERE (owner='Y' OR visitor='E') AND visible='Y')");

        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder);

        $shares           = [];
        $getUserNameModel = new GetUserNameModel($database);
        if (count($results) >= 1) {
            foreach ($results as $r) {
                try {
                    // get username
                    $username = $getUserNameModel->getUserName($r->userid);

                    if ($r->visitor === 'E') {
                        $share = new ShareModel($username, 'edit');
                    } else {
                        $share = new ShareModel($username, 'view');
                    }
                    $shares[] = $share;
                } catch (UserUnknownException $e) {
                    $this->logger->warning("User '$r->userid' unknown, skipping share for this user.");
                }
            }
        }
        $database->close();
        return $shares;
        // return [new Share('Sebastian', 'edit'), new Share('Simon', 'edit'), new Share('Alex', 'view'), new Share('Janko', 'view')];
    }


    /**
     * Get the amount of users, the folder is being shared with (excluding the owner).
     *
     * @param int $folderId folder ID
     *
     * @return int amount of users the folder is being shared with
     */
    public function getShareFolderCount(int $folderId): int
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("COUNT(user_id) AS amount");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsBool("owner", false);
        $queryBuilder->andWhereEqualsStr("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // execute query
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder, 1);
        $database->close();
        return $results[0]->amount;
    }


}
