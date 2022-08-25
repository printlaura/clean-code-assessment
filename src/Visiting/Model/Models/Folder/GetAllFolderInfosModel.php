<?php

namespace App\Visiting\Model\Models\Folder;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Model\Models\Comment\ShowCommentsModel;
use App\Visiting\Model\Models\Description\DescriptionModel;
use App\Visiting\Model\Models\Share\ShowFolderShareModel;
use App\Visiting\Model\Models\User\GetUserNameModel;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Folder GetAllFolderInfosModel
 */
class GetAllFolderInfosModel extends Model
{


    /**
     * Get all infos of a single user folder
     *
     * @param LoggerInterface $logger    logger
     * @param int             $folderId  folder that infos will be retrieved from
     * @param bool            $hashFlagg when false, shows hashs
     *
     * @return FolderModel
     * @throws FolderUnknownException
     */
    public static function getAllFolderInfos(LoggerInterface $logger, int $folderId, bool $hashFlagg): FolderModel
    {
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement(self::getSqlFolder($folderId));
        if (count($results) > 1) {
            throw new UnexpectedValueException("Database ERROR: more than one folder id");
        } else if (count($results) < 1) {
            throw new FolderUnknownException($folderId);
        }

        $resultFolder = $results[0];
        // get folder details
        list($commentCount, $shareCount, $ownerName, $mediaRights) = self::getFolderInfoDetails($logger, $folderId);
        $description = DescriptionModel::get($logger, $folderId);
        // get picture/video count
        list($imageCount, $videoCount) = self::getMediaCount($logger, $folderId);

        if ($hashFlagg === true) {
            $outputHashView = null;
            $outputHashEdit = null;
        } else {
            $outputHashView = utf8_encode($resultFolder->viewhash);
            $outputHashEdit = utf8_encode($resultFolder->edithash);
        }

        return new FolderModel(
            utf8_encode($resultFolder->name),
            $description,
            $outputHashView,
            $outputHashEdit,
            new CustomDateTimeModel($resultFolder->created),
            new CustomDateTimeModel($resultFolder->updated),
            $imageCount,
            $videoCount,
            $commentCount,
            $shareCount,
            $ownerName,
            $mediaRights
        );
    }


    /**
     * Returns Folder Details using different Models
     *
     * @param LoggerInterface $logger   logger reference
     * @param int             $folderId folder reference
     *
     * @return array $commentCount, $shareCount, $description, $ownerName, $mediaRights
     * @throws FolderUnknownException when the folderId does not reference a folder
     */
    public static function getFolderInfoDetails(LoggerInterface $logger, int $folderId): array
    {
        $mediaRights = "view";
        // get comment count
        $showCommentsModel = new ShowCommentsModel($logger);
        $commentCount      = $showCommentsModel->getFolderCommentCount($folderId);
        // get share count
        $showFolderShareModel = new ShowFolderShareModel($logger);
        $shareCount           = $showFolderShareModel->getShareFolderCount($folderId);
        // get ownerName
        $database  = new Database($logger);
        $getName   = new GetUserNameModel($database);
        $ownerName = $getName->getOwnerName($folderId);
        $database->close();

        return [
            $commentCount,
            $shareCount,
            $ownerName,
            $mediaRights,
        ];
    }


    /**
     * Returns Folder Details for multiple folders using different Models
     *
     * @param LoggerInterface $logger
     * @param int[]           $folderIds
     *
     * @return array dict with folderId as key
     */
    public static function getMultipleFolderInfoDetails(LoggerInterface $logger, array $folderIds): array
    {
        $showCommentsModel = new ShowCommentsModel($logger);
        $commentCountsDict = $showCommentsModel->getMutlitpleFoldersCommentCount($folderIds);

        $showFolderShareModel = new ShowFolderShareModel($logger);
        $shareCountsDict      = $showFolderShareModel->getMultipleFoldersShareCount($folderIds);


        // get ownerName
        $database      = new Database($logger);
        $getName       = new GetUserNameModel($database);
        $ownerNameDict = $getName->getMultipleOwnerNames($folderIds);
        $database->close();

        $folderDetailsDict = [];
        foreach ($folderIds as $folderId) {
            $folderDetailsDict[$folderId] = [
                $commentCountsDict[$folderId],
                $shareCountsDict[$folderId],
                $ownerNameDict[$folderId],
                "view",
            ];
        }
        return $folderDetailsDict;
    }


    /**
     * Read the folder infos from the database like hashes and description
     *
     * @param int $folderId folder ID
     *
     * @return QueryBuilderSelect
     */
    private static function getSqlFolder(int $folderId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "name",
            "viewhash",
            "edithash",
            // "description", Column NOT used
            "created",
            "updated"
        );
        $queryBuilder->from("web_lb_folder");
        $queryBuilder->andWhereEqualsInt("id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        return $queryBuilder;
    }


    /**
     * Retrieves folder name using folder id
     *
     * @param $database
     * @param $folderId
     *
     * @return string folder name
     */
    public static function getFolderName($database, $folderId): string
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("name");
        $queryBuilder->from("web_lb_folder");
        $queryBuilder->andWhereEqualsInt("id", $folderId);
        $results = $database->queryPreparedStatement($queryBuilder, 1);
        return (string) $results[0]->name;
    }


    /**
     * Get media count in a folder
     *
     * @param LoggerInterface $logger
     * @param int             $folderId
     *
     * @return int[] imageCount, videoCount
     */
    private static function getMediaCount(LoggerInterface $logger, int $folderId): array
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("media_id", "type", "source");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);

        $database    = new Database($logger);
        $resultMedia = $database->queryPreparedStatement($queryBuilder);

        $imageCount = 0;
        $videoCount = 0;
        foreach ($resultMedia as $media) {
            $mediaRef = new MediaReferenceModel(
                $media->source,
                $media->media_id,// phpcs:ignore
                $media->type
            );
            if ($mediaRef->isImage() === true) {
                $imageCount += 1;
            } else {
                $videoCount += 1;
            }
        }
        return [
            $imageCount,
            $videoCount,
        ];
    }


    /**
     * Get media count for multiple folders
     *
     * @param LoggerInterface $logger
     * @param array           $folderIds
     *
     * @return array        dict containing image count and video count for all folderIds
     */
    public static function getMultipleFoldersMediaCount(LoggerInterface $logger, array $folderIds): array
    {
        $queryBuilderImages = new QueryBuilderSelect();
        $queryBuilderImages->select("folder_id AS folderid", "COUNT(media_id) AS amount");
        $queryBuilderImages->from("web_lb_folder_media");
        $queryBuilderImages->andWhereEqualsBool("visible", true);
        $queryBuilderImages->andWhereEqualsStr("type", "I");
        $queryBuilderImages->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilderImages->groupBy("folder_id");

        $queryBuilderVideos = new QueryBuilderSelect();
        $queryBuilderVideos->select("folder_id AS folderid", "COUNT(media_id) AS amount");
        $queryBuilderVideos->from("web_lb_folder_media");
        $queryBuilderVideos->andWhereEqualsBool("visible", true);
        $queryBuilderVideos->andWhereEqualsStr("type", "V");
        $queryBuilderVideos->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilderVideos->groupBy("folder_id");

        $database     = new Database($logger);
        $resultImages = $database->queryPreparedStatement($queryBuilderImages);
        $resultVideos = $database->queryPreparedStatement($queryBuilderVideos);
        $database->close();

        $dictImages = [];
        foreach ($resultImages as $resultImage) {
            $dictImages[$resultImage->folderid] = $resultImage->amount;
        }
        $dictVideos = [];
        foreach ($resultVideos as $resultVideo) {
            $dictVideos[$resultVideo->folderid] = $resultVideo->amount;
        }

        $dict = [];
        foreach ($folderIds as $folderId) {
            if (isset($dictVideos[$folderId]) !== false) {
                $videoCount = $dictVideos[$folderId];
            } else {
                $videoCount = 0;
            }
            if (isset($dictImages[$folderId]) !== false) {
                $imageCount = $dictImages[$folderId];
            } else {
                $imageCount = 0;
            }
            $dict[$folderId] = [
                "image" => $imageCount,
                "video" => $videoCount,
            ];
        }
        return $dict;
    }


}
