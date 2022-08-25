<?php

namespace App\Authenticated\Model\Models\Sort;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Activities\ActivityModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * MakeIndividualSortModel class
 * edit the order of media in a folder
 */
class MakeIndividualSortModel extends Model
{

    /**
     * Logging errors and warnings
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
     * Edit the order of media in a folder
     *
     * @param int                 $userId         user that executed the sort edit
     * @param int                 $folderId       folder the media is in
     * @param MediaReferenceModel $mediaReference the media that will be rearranged
     * @param int                 $oldSort        current position of the media (starts with 0)
     * @param int                 $newSort        new position of the media
     *
     * @return void
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user does not have edit rights
     */
    public function makeIndividualSort(int $userId, int $folderId, MediaReferenceModel $mediaReference, int $oldSort, int $newSort)
    {
        // test for edit rights
        $getFolderRightsModel = new GetFolderRightsModel($this->logger);
        $getFolderRightsModel->assertEdit($userId, $folderId);

        $database = new Database($this->logger);
        self::testArguments($database, $folderId, $oldSort, $newSort);

        // rearrange all the media that is "in the way" of the referenced media
        $sqlRearrangeOtherMedia = null;
        if ($oldSort < $newSort) {
            // move all media one down in the list, that is between oldSort and newSort
            $sqlRearrangeOtherMedia = new QueryBuilderUpdate();
            $sqlRearrangeOtherMedia->update("web_lb_folder_media");
            $sqlRearrangeOtherMedia->addSetFunc("sort", "sort-1");
            $sqlRearrangeOtherMedia->andWhereEqualsInt("folder_id", $folderId);
            $sqlRearrangeOtherMedia->andWhereGreaterEqual("sort", $oldSort);
            $sqlRearrangeOtherMedia->andWhereSmallerEqual("sort", $newSort);
        }
        if ($oldSort > $newSort) {
            // move all media one up in the list, that is between newSort and oldSort
            $sqlRearrangeOtherMedia = new QueryBuilderUpdate();
            $sqlRearrangeOtherMedia->update("web_lb_folder_media");
            $sqlRearrangeOtherMedia->addSetFunc("sort", "sort+1");
            $sqlRearrangeOtherMedia->andWhereEqualsInt("folder_id", $folderId);
            $sqlRearrangeOtherMedia->andWhereGreaterEqual("sort", $newSort);
            $sqlRearrangeOtherMedia->andWhereSmallerEqual("sort", $oldSort);
        }
        $database->executePreparedStatement($sqlRearrangeOtherMedia);
        // set target medias new sort value
        $queryBuilderMedia = new QueryBuilderUpdate();
        $queryBuilderMedia->update("web_lb_folder_media");
        $queryBuilderMedia->addSetInt("sort", $newSort);
        $queryBuilderMedia->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilderMedia->andWhereEqualsStr("source", $mediaReference->source);
        $queryBuilderMedia->andWhereEqualsInt("media_id", $mediaReference->id);
        $database->executePreparedStatement($queryBuilderMedia);
        // activity
        ActivityModel::insertMakeIndividualSortActivity($database, $userId, $folderId);
        $database->close();
    }


    /**
     * Returns the sort value of a new media added to the folder
     *
     * @param int $folderId folder that requires a new sort value
     *
     * @return int sort value for a new media
     */
    public function getNewSortValue(int $folderId): int
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("COUNT(sort) AS amount");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $database = new Database($this->logger);
        $results  = $database->queryPreparedStatement($queryBuilder, 1);
        $database->close();
        return (int) $results[0]->amount;
    }


    /**
     * Test arguments to prevent errors later in the code.
     *
     * @param Database $database    database connection reference
     * @param int      $folderId    folder the media is in
     * @param int      $currentSort current position of the media (starts with 0)
     * @param int      $newSort     new position of the media
     *
     * @throws InvalidArgumentException when arguments would lead to errors
     * @return void
     */
    private static function testArguments(Database $database, int $folderId, int $currentSort, int $newSort)
    {
        if ($currentSort < 0) {
            throw new InvalidArgumentException("oldSort must be greater than or equal to 0");
        }
        if ($newSort < 0) {
            throw new InvalidArgumentException("newSort must be greater than or equal to 0");
        }
        if ($currentSort === $newSort) {
            throw new InvalidArgumentException("oldSort can't be equal to newSort");
        }
        // these tests need to be executed, otherwise there wouldn't be an error thrown when they don't exist.
        // if they don't exist, but the queries will still be executed, this could mess up the complete sort structure
        // test if oldSort exists
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("sort");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsInt("sort", $currentSort);
        try {
            $database->queryPreparedStatement($queryBuilder, 1);
        } catch (UnexpectedValueException $exception) {
            throw new InvalidArgumentException("newSort does not exist");
        }
        // test if newSort exists
        $queryBuilder = null;
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("sort");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsInt("sort", $newSort);
        try {
            $database->queryPreparedStatement($queryBuilder, 1);
        } catch (UnexpectedValueException $exception) {
            throw new InvalidArgumentException("newSort does not exist");
        }
    }


}
