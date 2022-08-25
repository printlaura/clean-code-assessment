<?php

namespace App\Authenticated\Model\Models\Activities;

use App\Authenticated\Controller\Actions\Comment\AddCommentAction;
use App\Authenticated\Controller\Actions\Description\AddDescriptionAction;
use App\Authenticated\Controller\Actions\Folder\CreateFolderAction;
use App\Authenticated\Controller\Actions\Folder\DeleteFolderAction;
use App\Authenticated\Controller\Actions\Folder\RemoveFolderAction;
use App\Authenticated\Controller\Actions\Folder\RenameFolderAction;
use App\Authenticated\Controller\Actions\Media\AddMediaToFolderAction;
use App\Authenticated\Controller\Actions\Media\RemoveMediaFromFolderAction;
use App\Authenticated\Controller\Actions\Share\AddForeignFolderAction;
use App\Authenticated\Controller\Actions\Sort\MakeIndividualSortAction;
use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserUnknownException;
use App\Visiting\Model\Models\Folder\FolderModel;
use App\Visiting\Model\Models\Folder\GetAllFolderInfosModel;
use App\Visiting\Model\Models\User\GetUserNameModel;

/**
 * Activity class
 */
class ActivityModel extends Model
{

    /**
     * Action that was executed, must be the same as the actions names.
     *
     * @var string
     */
    public string $action;

    /**
     * Variable for the action date
     *
     * @var CustomDateTimeModel|null
     */
    public ?CustomDateTimeModel $actionDate;

    /**
     * Username of the user that performed the activity.
     * We decided not to give the user ID here, in order to prevent userId scraping.
     *
     * @var string|null
     */
    public ?string $actor;

    /**
     * Identification number for this activity
     *
     * @var integer|null
     */
    public ?int $id;

    /**
     * Reference to the object the activity happened on.
     *
     * @var string
     *
     * @example Image: /image/st/9263932
     * @example Video: /video/sp/1232377
     * @example Folder:  /folder/123
     * @example Comment: /comment/283522
     * @example Image added to Folder: /folder/123/st/9263932
     */
    public string $link;

    /**
     * Y when the activity was read by the user, otherwise N
     *
     * @var string
     */
    public string $read;

    /**
     * Needed for building the folder link
     *
     * @var integer|null identification number for folder
     */
    public ?int $folderid;

    public ?string $foldername;

    public ?string $prevfoldername;


    /**
     * Constructor define the activity model
     *
     * @param int|null                 $id                 Activity ID
     * @param string|null              $link               reference on the object the activity happened on, for more information
     *                                                     look into the documentation of the link parameter in Activity.php look
     *                                                     into the documentation of the link parameter in Activity.php
     * @param int|null                 $folderId           folder ID
     * @param string|null              $folderName
     * @param string|null              $previousFolderName
     * @param string|null              $actor              name of the user that executed the activity
     * @param string                   $action             action name that was executed @example CreateFolderAction::NAME
     * @param CustomDateTimeModel|null $actionDate         time the action was executed
     * @param bool                     $isRead             if the user has marked the activity as read
     */
    public function __construct(
        ?int                 $id,
        ?string              $link,
        ?int                 $folderId,
        ?string              $folderName,
        ?string              $previousFolderName,
        ?string              $actor,
        string               $action,
        ?CustomDateTimeModel $actionDate,
        bool                 $isRead = false
    ) {
        $this->id         = $id;
        $this->actor      = $actor;
        $this->action     = utf8_encode($action);
        $this->actionDate = $actionDate;
        $this->folderid   = $folderId;
        $this->foldername = $folderName;
        $this->prevfoldername = $previousFolderName;
        $this->read           = Database::getDbValueFromBool($isRead);
        $this->link           = utf8_encode($link);
    }


    /**
     * Inserts a new AddComment activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param int      $commentId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddFolderCommentActivity(Database $database, int $userId, int $folderId, int $commentId)
    {
        $activity = new ActivityModel(
            null,
            "/comment/$commentId",
            $folderId,
            GetAllFolderInfosModel::getFolderName($database, $folderId),
            AddCommentAction::FOLDER,
            null,
            AddCommentAction::FOLDER,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new AddDescription activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddFolderDescriptionActivity(Database $database, int $userId, int $folderId)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            GetAllFolderInfosModel::getFolderName($database, $folderId),
            null,
            null,
            AddDescriptionAction::FOLDER,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Sets the activity's actor to a username, using the user id.
     *
     * @param int      $userId   used to get username
     * @param Database $database
     *
     * @return null
     * @throws UserUnknownException
     */
    public function setActor(int $userId, Database $database)
    {
        if (isset($this->actor) === false) {
            $getUserName = new GetUserNameModel($database);
            $this->actor = $getUserName->getUserName($userId);
        }
        return null;
    }


    /**
     * Inserts a new AddForeignFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddForeignFolderActivity(Database $database, int $userId, int $folderId)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            null,
            null,
            null,
            AddForeignFolderAction::ADD,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new AddComment activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param int      $commentId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddMediaCommentActivity(Database $database, int $userId, int $folderId, int $commentId)
    {
        $activity = new ActivityModel(
            null,
            "/comment/$commentId",
            $folderId,
            null,
            null,
            null,
            AddCommentAction::MEDIA,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new AddDescription activity
     *
     * @param Database            $database
     * @param int                 $userId
     * @param int                 $folderId
     * @param MediaReferenceModel $mediaReference
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddMediaDescriptionActivity(Database $database, int $userId, int $folderId, MediaReferenceModel $mediaReference)
    {
        $activity = new ActivityModel(
            null,
            "/$mediaReference->source/$mediaReference->id",
            $folderId,
            null,
            null,
            null,
            AddDescriptionAction::MEDIA,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new AddMediaToFolder activity
     *
     * @param Database            $database
     * @param int                 $userId
     * @param int                 $folderId
     * @param MediaReferenceModel $mediaReference
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertAddMediaToFolderActivity(Database $database, int $userId, int $folderId, MediaReferenceModel $mediaReference)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId/$mediaReference->source/$mediaReference->id",
            $folderId,
            GetAllFolderInfosModel::getFolderName($database, $folderId),
            null,
            null,
            AddMediaToFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Insert a new CreateFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param string   $folderName
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertCreateFolderActivity(Database $database, int $userId, int $folderId, string $folderName)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            $folderName,
            null,
            null,
            CreateFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new DeleteFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param string   $folderName
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertDeleteFolderActivity(Database $database, int $userId, int $folderId, string $folderName)
    {

        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            $folderName,
            null,
            null,
            DeleteFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new MakeIndividualSort activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertMakeIndividualSortActivity(Database $database, int $userId, int $folderId)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            null,
            null,
            null,
            MakeIndividualSortAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new RemoveFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param string   $folderName
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertRemoveFolderActivity(Database $database, int $userId, int $folderId, string $folderName)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            $folderName,
            null,
            null,
            RemoveFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new RemoveMediaFromFolder activity
     *
     * @param Database            $database
     * @param int                 $userId
     * @param int                 $folderId
     * @param MediaReferenceModel $mediaReference
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertRemoveMediaFromFolderActivity(Database $database, int $userId, int $folderId, MediaReferenceModel $mediaReference)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId/$mediaReference->source/$mediaReference->id",
            $folderId,
            GetAllFolderInfosModel::getFolderName($database, $folderId),
            null,
            null,
            RemoveMediaFromFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new RenameFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     * @param string   $previousFolderName
     * @param string   $folderName
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertRenameFolderActivity(Database $database, int $userId, int $folderId, string $previousFolderName, string $folderName)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            $folderName,
            $previousFolderName,
            null,
            RenameFolderAction::NAME,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Inserts a new UpdateForeignFolder activity
     *
     * @param Database $database
     * @param int      $userId
     * @param int      $folderId
     *
     * @return void
     * @throws UserUnknownException
     */
    public static function insertUpdateForeignFolderActivity(Database $database, int $userId, int $folderId)
    {
        $activity = new ActivityModel(
            null,
            "/folder/$folderId",
            $folderId,
            null,
            null,
            null,
            AddForeignFolderAction::UPDATE_FOREIGN_FOLDER_RIGHTS,
            null
        );
        $activity->setActor($userId, $database);
        $activity->insert($userId, $database);
    }


    /**
     * Creates query to insert activity into database
     *
     * @param int      $userId   user id must be added, because activity only contains username
     * @param Database $database database reference, used for insert
     *
     * @return void
     */
    private function insert(int $userId, Database $database)
    {
        $queryBuilderActivity = new QueryBuilderInsert();
        $this->action         = utf8_encode($this->action);
        $this->link           = utf8_encode($this->link);
        $queryBuilderActivity->insert("web_lb_activities");
        $queryBuilderActivity->insertValueStr("activity", "$this->action");
        $queryBuilderActivity->insertValueStr("link", "$this->link");
        $queryBuilderActivity->insertValueInt("user_id", $userId);
        if (isset($this->foldername) === true) {
            $queryBuilderActivity->insertValueStr("folder_name", $this->foldername);
        }
        if (isset($this->prevfoldername) === true) {
            $queryBuilderActivity->insertValueStr("previous_folder_name", $this->prevfoldername);
        }
        $queryBuilderActivity->insertValueFunc("activity_date", "SYSDATETIME()");
        $queryBuilderActivity->insertValueBool("visible", true);
        $queryBuilderActivity->output("id");
        if (isset($this->folderid) === true) {
            $queryBuilderActivity->insertValueInt("object_id", $this->folderid);
            $queryBuilderActivity->insertValueStr("object_type", "F");
        }
        $this->id = (int) $database->executePreparedStatement($queryBuilderActivity, true);


        if (isset($this->folderid) === true) {
            // add activity to all users in the folder
            $sqlUsersInFolder = FolderModel::getUsersQuery($this->folderid);
            $results          = $database->queryPreparedStatement($sqlUsersInFolder);
            foreach ($results as $folderMemberUserId) {
                $this->insertIsReadUser($database, (int)$folderMemberUserId->user_id);// phpcs:ignore
            }
        } else {
            $this->insertIsReadUser($database, $userId);
        }
    }


    /**
     * Inserts is read for this activity and user
     *
     * @param Database $database reference to insert
     * @param int      $userId   user, the activity will be marked for as read or unread
     *
     * @return void
     */
    private function insertIsReadUser(Database $database, int $userId)
    {
        $queryBuilderUser = new QueryBuilderInsert();
        $queryBuilderUser->insert("web_lb_activities_user");
        $queryBuilderUser->insertValueInt("activity_id", $this->id);
        $queryBuilderUser->insertValueBool("isread", Database::getBoolFromDbValue($this->read));
        $queryBuilderUser->insertValueInt("user_id", $userId);
        $queryBuilderUser->insertValueBool("visible", true);
        $database->executePreparedStatement($queryBuilderUser);
    }


}
