<?php

namespace App\Authenticated\Model\Models\Activities;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilder;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Visiting\Model\Models\User\GetUserNameModel;
use Psr\Log\LoggerInterface;

/**
 * ShowActivitiesModel Class
 */

class ShowActivitiesModel extends Model
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
     * Returns array of activities depending on the user, object and filter
     *
     * @param integer  $userId                       user that wants to see the activities
     * @param int|null $folderId                     when null all activities will be show, if set only activities on one folder will be shown (user must have view access).
     * @param bool     $onlyShowOtherUsersActivities when set true the activities the user created will not get returned
     *
     * @return ActivityModel[]
     * @throws UserMissingRightsException when user is not allowed to view folder
     */
    public function showActivities(int $userId, int $folderId = null, bool $onlyShowOtherUsersActivities = false): array
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "id",
            "object_id",
            "object_type",
            "source",
            "folder_name",
            "previous_folder_name",
            "link",
            "user_id",
            "activity",
            "activity_date"
        );
        $queryBuilder->from("web_lb_activities");
        // get all activities where the user is involved (shared folder), not only the activities the user created
        $queryIdInvolvedActivities = new QueryBuilder();
        $queryIdInvolvedActivities->select("activity_id");
        $queryIdInvolvedActivities->from("web_lb_activities_user");
        $queryIdInvolvedActivities->andWhereEqualsInt("user_id", $userId);
        $queryIdInvolvedActivities->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereInFunc("id", "($queryIdInvolvedActivities)");
        $queryBuilder->andWhereEqualsBool("visible", true);
        // don't show the activities created by the user
        if ($onlyShowOtherUsersActivities === true) {
            $queryBuilder->andWhereEqualsInt("user_id", $userId, false);
        }
        if (isset($folderId) === true) {
            // only show activities on one folder
            // test for view permissions
            $getFolderRights = new GetFolderRightsModel($this->logger);
            $getFolderRights->assertView($userId, $folderId);

            $queryBuilder->andWhereEqualsStr("object_type", "F");
            $queryBuilder->andWhereEqualsInt("object_id", $folderId);
        }
        $queryBuilder->sort("created DESC");
        // execute query
        $database        = new Database($this->logger);
        $resultsActivity = $database->queryPreparedStatement($queryBuilder);
        // parse into activities
        $activityIds = [];
        foreach ($resultsActivity as $row) {
            $activityIds[] = $row->id;
        }
        $hasUserReadMultipleActivities = $this->hasUserReadMultipleActivities($userId, $activityIds, $database);
        $activities  = [];
        $getUserName = new GetUserNameModel($database);
        foreach ($resultsActivity as $row) {
            if (isset($row->link) === false) {
                $this->logger->warning("Activity in database does not contain link, will be skipped. UserId: $row->user_id ActivityId: $row->id");
                continue;
            }
            try {
                $actor = $getUserName->getUserName($row->user_id);// phpcs:ignore
            } catch (UserUnknownException $e) {
                // skip this activity
                $this->logger->warning($e->getMessage()." Activity will be skipped.");
                continue;
            }
            $activity     = new ActivityModel(
                $row->id,
                $row->link,
                null,
                $row->folder_name,// phpcs:ignore
                $row->previous_folder_name,// phpcs:ignore
                $actor,
                $row->activity,
                new CustomDateTimeModel($row->activity_date),// phpcs:ignore
                $hasUserReadMultipleActivities[$row->id]
            );
            $activities[] = $activity;
        }
        $database->close();
        return $activities;
    }


    /**
     * Dictionary with activity id and bool value that is true when user has read activity, false when user has not read activity yet
     *
     * @param int      $userId      user that has read the activity or not
     * @param int[]    $activityIds activities that have been read or not
     * @param Database $database    database to execute sql query
     *
     * @return array
     */
    private function hasUserReadMultipleActivities(int $userId, array $activityIds, Database $database): array
    {
        if (count($activityIds) === 0) {
            return [];
        }
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("activity_id AS activityid", "isread");
        $queryBuilder->from("web_lb_activities_user");
        $queryBuilder->andWhereEqualsInt("user_id", $userId);
        $queryBuilder->andWhereIsInIntArray("activity_id", $activityIds);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // execute query
        $results = $database->queryPreparedStatement($queryBuilder);

        $dict = [];
        // add read to dict
        foreach ($results as $result) {
            $dict[(int) $result->activityid] = Database::getBoolFromDbValue($result->isread);
        }
        // set default read to ""
        foreach ($activityIds as $activityId) {
            if (isset($dict[$activityId]) === false) {
                $dict[$activityId] = false;
            }
        }
        return $dict;
    }


}
