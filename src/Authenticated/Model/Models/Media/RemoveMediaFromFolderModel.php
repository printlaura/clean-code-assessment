<?php

namespace App\Authenticated\Model\Models\Media;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use Psr\Log\LoggerInterface;

/**
 * Remove media from folder
 */
class RemoveMediaFromFolderModel extends Model
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
     * Remove media from folder.
     * Does not delete database entry, but turns it invisible to allow for undo functionality.
     *
     * @param int                 $userId         user that wants to remove media from folder
     * @param int                 $folderId       folder the media will be removed from
     * @param MediaReferenceModel $mediaReference media that will be removed
     *
     * @return void
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user is not allowed to edit the folder
     */
    public function removeMediaFromFolder(int $userId, int $folderId, MediaReferenceModel $mediaReference)
    {
        // test for edit rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertEdit($userId, $folderId);
        // remove media
        $queryBuilder = new QueryBuilderUpdate();
        $queryBuilder->update("web_lb_folder_media");
        $queryBuilder->addSetStr("visible", "N");
        $queryBuilder->addSetFunc("updated", "SYSDATETIME()");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        $queryBuilder->andWhereEqualsInt("media_id", $mediaReference->id);
        // execute command
        $database = new Database($this->logger);
        $database->executePreparedStatement($queryBuilder);
        // add activity
        ActivityModel::insertRemoveMediaFromFolderActivity($database, $userId, $folderId, $mediaReference);
        $database->close();
    }


}
