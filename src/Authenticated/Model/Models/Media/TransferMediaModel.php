<?php

namespace App\Authenticated\Model\Models\Media;

use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use BadFunctionCallException;
use Psr\Log\LoggerInterface;

/**
 * Transfer media to another folder.
 * There is no need to test for folder rights here, because this is done in all the sub models (f. ex. AddMediaToFolder)
 */
class TransferMediaModel extends Model
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
     * Copy a media to a destination folder. Media will NOT be removed from source folder.
     *
     * @param int                 $userId              user that is copying media
     * @param MediaReferenceModel $mediaReference      does not require type, but would increase performance
     * @param mixed               $sourceFolderId      id of the folder, media will be copied from
     * @param int                 $destinationFolderId id of the folder, media will be copied to
     *
     * @return void
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user does not have edit rights on destination folder
     * @throws MediaUnknownException when media not found
     */
    public function copyMedia(int $userId, MediaReferenceModel $mediaReference, int $sourceFolderId, int $destinationFolderId)
    {
        try {
            $mediaReference->assertType();
        } catch (BadFunctionCallException $exception) {
            $mediaReference = $this->getMediaType($mediaReference, $sourceFolderId);
        }
        $addMediaToFolderModel = new AddMediaToFolderModel($this->logger);
        $addMediaToFolderModel->addMediaToFolder($userId, $destinationFolderId, $mediaReference);
    }


    /**
     * Move media from a folder and add it to another folder. Media will be removed from source folder.
     *
     * @param mixed               $userId              user that is moving the media
     * @param MediaReferenceModel $mediaReference      does not require type, but would increase performance
     * @param mixed               $sourceFolderId      id of the folder, media will be removed from
     * @param mixed               $destinationFolderId id of the folder, media will be moved to
     *
     * @return void
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user does not have edit rights on destination folder
     * @throws MediaUnknownException when media not found
     */
    public function moveMedia(string $userId, MediaReferenceModel $mediaReference, int $sourceFolderId, int $destinationFolderId)
    {
        try {
            $mediaReference->assertType();
        } catch (BadFunctionCallException $exception) {
            $mediaReference = $this->getMediaType($mediaReference, $sourceFolderId);
        }
        $removeMediaFromFolderModel = new RemoveMediaFromFolderModel($this->logger);
        $removeMediaFromFolderModel->removeMediaFromFolder($userId, $sourceFolderId, $mediaReference);
        $addMediaToFolderModel = new AddMediaToFolderModel($this->logger);
        $addMediaToFolderModel->addMediaToFolder($userId, $destinationFolderId, $mediaReference);
    }


    /**
     * Queries media type from database and adds it to media reference
     *
     * @param MediaReferenceModel $mediaReference reference to the media object
     * @param int                 $sourceFolderId folder the type will be queried from
     *
     * @return MediaReferenceModel containing type
     */
    private function getMediaType(MediaReferenceModel $mediaReference, int $sourceFolderId): MediaReferenceModel
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("type");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $sourceFolderId);
        $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        $queryBuilder->andWhereEqualsInt("media_id", $mediaReference->id);
        // execute
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder, 1);
        $database->close();
        return new MediaReferenceModel($mediaReference->source, $mediaReference->id, $results[0]->type);
    }


}
