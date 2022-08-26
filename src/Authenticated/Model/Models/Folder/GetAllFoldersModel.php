<?php

namespace App\Authenticated\Model\Models\Folder;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Model\Models\Description\DescriptionModel;
use App\Visiting\Model\Models\Folder\FolderModel;
use App\Visiting\Model\Models\Folder\GetAllFolderInfosModel;
use App\Unauthenticated\Model\Models\Media\PreviewModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;


class GetAllFoldersModel extends Model
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Read all user folders from the DB
     *
     * @param int    $userId    user reference, which folders will be returned
     * @param string $sortBy    column to sort by must be one of 'name', 'update_date'
     * @param string $sortOrder asc or desc
     * @param int    $limit     maximum amount of folders
     * @param int    $offset    amount of folders to skipp
     * @param string $language  language (en or de)
     *
     * @return FolderModel[]
     */
    public function getAllFolders(int $userId, string $sortBy, string $sortOrder, int $limit, int $offset, string $language): array
    {
        $sqlFolders = self::generateSql($userId, $sortBy, $sortOrder, $limit, $offset);
        $database   = new Database($this->logger);
        $results    = $database->queryPreparedStatement($sqlFolders);
        $database->close();
        $folders = [];

        $folderIds = [];
        foreach ($results as $result) {
            $folderIds[] = (int) $result->id;
        }
        if (count($folderIds) < 1) {
            return [];
        }
        $descriptionsDict  = DescriptionModel::getMultipleFolderDescriptions($this->logger, $folderIds);
        $folderDetailsDict = GetAllFolderInfosModel::getMultipleFolderInfoDetails($this->logger, $folderIds);
        $previewsDict      = $this->getMultiplePreviews($database, $folderIds, $language);
        $mediaCountDict    = GetAllFolderInfosModel::getMultipleFoldersMediaCount($this->logger, $folderIds);

        foreach ($results as $result) {
            $folderId = (int) $result->id;
            // get folder details
            list($commentCount, $shareCount, $ownerName, $mediaRights) = $folderDetailsDict[$folderId];
            // create folder instance
            $folder = new FolderModel(
                $result->name,
                $descriptionsDict[$folderId],
                utf8_encode($result->viewhash),
                utf8_encode($result->edithash),
                new CustomDateTimeModel($result->created),
                new CustomDateTimeModel($result->updated),
                $mediaCountDict[$folderId]["image"],
                $mediaCountDict[$folderId]["video"],
                $commentCount,
                $shareCount,
                $ownerName,
                $mediaRights,
                $sortBy,
                $sortOrder,
                $limit,
                $offset,
                $previewsDict[$folderId]
            );
            $hashOrFolder["id"] = $folderId;
            $output    = array_merge($hashOrFolder, (array) $folder);
            $folders[] = $output;
        }
        
        return $folders;
    }


    /**
     * Get the previews of images in multiple folders
     *
     * @param Database $database  database connection
     * @param int[]    $folderIds
     * @param string   $language  'en' or default (de)
     *
     * @return array dict with folderId as key and Preview[] as values
     */
    private function getMultiplePreviews(Database $database, array $folderIds, string $language): array
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("folder_id AS folderid", "media_id AS mediaid", "type", "source", "sort");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilder->sort("folder_id", "sort");

        $resultMedias = $database->queryPreparedStatement($queryBuilder);

        $folderMediaRefArrayDict = [];
        foreach ($resultMedias as $result) {
            if (isset($folderMediaRefArrayDict[$result->folderid]) === true) {
                $mediaRefs = $folderMediaRefArrayDict[$result->folderid];
            } else {
                $mediaRefs = [];
            }
            if (empty($mediaRefs) === true || count($mediaRefs) < 4) {
                $mediaRefs[] = new MediaReferenceModel($result->source, $result->mediaid, $result->type);
            }
            $folderMediaRefArrayDict[$result->folderid] = $mediaRefs;
        }

        $folderPreviewsDict = [];
        foreach ($folderIds as $folderId) {
            if (isset($folderMediaRefArrayDict[$folderId]) === true) {
                $mediaRefs = $folderMediaRefArrayDict[$folderId];
            } else {
                $mediaRefs = [];
            }
            $previews = [];
            $sort     = 0;
            foreach ($mediaRefs as $mediaReference) {
                $mediaThumb = $mediaReference->getThumb($language);
                $mediaSrc   = $mediaReference->getSrc($language);

                try {
                    $preview    = new PreviewModel(
                        $mediaReference,
                        $mediaThumb,
                        $mediaSrc,
                        $sort
                    );
                    $sort      += 1;
                    $previews[] = $preview;
                } catch (InvalidArgumentException $exception) {
                    $this->logger->warning("Failed to construct Preview from database. Skipped Preview for this object.", [$exception]);
                }
            }

            $folderPreviewsDict[$folderId] = $previews;
        }
        return $folderPreviewsDict;
    }


    /**
     * @param int    $userId    user that retrieves folders
     * @param string $sortBy    sorting option
     * @param string $sortOrder sorting direction
     * @param int    $limit     limit per page
     * @param int    $offset    page offset
     *
     * @return QueryBuilderSelect
     */
    private static function generateSql(int $userId, string $sortBy, string $sortOrder, int $limit, int $offset): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "web_lb_folder.id",
            "web_lb_folder.name",
            "web_lb_folder.viewhash",
            "web_lb_folder.edithash",
            "web_lb_folder.created",
            "web_lb_folder.updated"
        );
        $queryBuilder->from("web_lb_folder, web_lb_folder_user");
        $queryBuilder->andWhereEqualsStr("web_lb_folder_user.user_id", $userId);
        $queryBuilder->andWhereEqualsBool("web_lb_folder.visible", true);
        $queryBuilder->andWhereEqualsBool("web_lb_folder_user.visible", true);
        $queryBuilder->andWhereEqualsFunc("web_lb_folder_user.folder_id", "web_lb_folder.id");

        if ($sortBy === 'name') {
            $queryBuilder->sort("NAME $sortOrder");
        } else if ($sortBy === 'update_date') {
            $queryBuilder->sort("web_lb_folder.updated $sortOrder");
        } else {
            $queryBuilder->sort("NAME $sortOrder");
        }

        $queryBuilder->limitOffset($limit, $offset);

        return $queryBuilder;
    }


    /**
     * @param int $userId
     *
     * @return int
     */
    public function getFolderCount(int $userId): int
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("count(id) as foldercount");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsStr("user_id", $userId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        $database = new Database($this->logger);
        $result   = $database->queryPreparedStatement($queryBuilder, 1)[0];
        return (int) $result->foldercount;
    }


}
