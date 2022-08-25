<?php

namespace App\Authenticated\Model\Models\Activities;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use Psr\Log\LoggerInterface;

/**
 * MarkActivitiesModel class
 */
class MarkActivitiesModel extends Model
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
     * Mark activity as read
     *
     * @param int $userId     user that marks activity
     * @param int $activityId activity ID
     *
     * @return null
     */
    public function markRead(int $userId, int $activityId)
    {
        self::markActivity($userId, $activityId, true);
        return;
    }


    /**
     * Mark activity as read or unread
     *
     * @param int     $userId     user that marks activity
     * @param int     $activityId activity ID
     * @param boolean $isRead     activity is read, true->read
     *
     * @return null
     */
    private function markActivity(int $userId, int $activityId, bool $isRead)
    {
        // build query
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_activities_user");
        $queryBuilder->addSetFunc("updated", "SYSDATETIME()");
        $isReadStr = Database::getDbValueFromBool($isRead);
        $queryBuilder->addSetStr("isread", $isReadStr);
        $queryBuilder->andWhereEqualsInt("user_id", $userId);
        $queryBuilder->andWhereEqualsInt("activity_id", $activityId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // execute query
        $database = new Database($this->logger);
        $database->executePreparedStatement($queryBuilder);
        $database->close();
        return;
    }


    /**
     * Mark activity as unread
     *
     * @param int $userId     user that marks activity
     * @param int $activityId activity ID
     *
     * @return null
     */
    public function markUnread(int $userId, int $activityId)
    {
        self::markActivity($userId, $activityId, false);
        return;
    }


}
