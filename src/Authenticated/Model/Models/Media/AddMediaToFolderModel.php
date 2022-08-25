<?php

namespace App\Authenticated\Model\Models\Media;

use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\Sort\MakeIndividualSortModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Unauthenticated\Model\Models\Media\Pond5Model;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use BadMethodCallException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * AddMediaToFolderModel class add a media (video or image) to a user folder
 */
class AddMediaToFolderModel extends Model
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
     * Add media to folder
     *
     * @param int                 $userId         user that is adding media to folder
     * @param int                 $folderId       folder the media will be added to
     * @param MediaReferenceModel $mediaReference needs type
     *
     * @return void
     * @throws UserMissingRightsException when user does not have edit rights
     * @throws BadMethodCallException when $mediaReference is missing type
     * @throws UserUnknownException when user not found
     * @throws MediaUnknownException when media does not exist
     */
    public function addMediaToFolder(
        int $userId,
        int $folderId,
        MediaReferenceModel $mediaReference
    ) {
        // check attributes
        $mediaReference->assertType();
        // test for edit rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertEdit($userId, $folderId);

        $makeIndividualSortModel = new MakeIndividualSortModel($this->logger);
        // sort value is being overwritten here, the media will be added at the end of the folder
        $sort = $makeIndividualSortModel->getNewSortValue($folderId);
        // check if media exists
        self::assertMediaExists($this->logger, $mediaReference);
        // build query
        $queryBuilder = new QueryBuilderInsert();
        $queryBuilder->insert("web_lb_folder_media");
        $queryBuilder->insertValueInt("folder_id", $folderId);
        $queryBuilder->insertValueInt("media_id", $mediaReference->id);
        $queryBuilder->insertValueStr("source", $mediaReference->source);
        $queryBuilder->insertValueStr("type", $mediaReference->getTypeAsIV());
        $queryBuilder->insertValueBool("visible", true);
        $queryBuilder->insertValueFunc("updated", "SYSDATETIME()");
        $queryBuilder->insertValueInt("sort", $sort);
        // execute query
        $database = new Database($this->logger);
        $database->executePreparedStatement($queryBuilder);
        ActivityModel::insertAddMediaToFolderActivity($database, $userId, $folderId, $mediaReference);
        $database->close();
        return;
    }


    /**
     * Throws MediaUnknownException when media does not exist in database.
     *
     * @param LoggerInterface     $logger
     * @param MediaReferenceModel $mediaReference
     *
     * @return void
     * @throws MediaUnknownException
     */
    private static function assertMediaExists(LoggerInterface $logger, MediaReferenceModel $mediaReference): void
    {
        if ($mediaReference->source === 'sp' || $mediaReference->source === 'st') {
            $queryBuilder = new QueryBuilderSelect();
            $queryBuilder->select("bilder.Bildnummer AS mediaId");
            $queryBuilder->from("bilder");
            $queryBuilder->andWhereEqualsInt("bilder.Bildnummer", $mediaReference->id);
            $database = new Database($logger, $mediaReference->source);
            try {
                $database->queryPreparedStatement($queryBuilder, 1);
            } catch (UnexpectedValueException $unexpectedValueException) {
                throw new MediaUnknownException($mediaReference);
            }
        } else if ($mediaReference->source === 'pond5') {
            try {
                Pond5Model::getMediaModel($logger, $mediaReference->id);
            } catch (LicenseUnknownException $e) {
                throw new MediaUnknownException($mediaReference);
            }
        }
    }


}
