<?php

namespace App\Visiting\Model\Models\Media;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Class for getting the previous and next media id
 */
class GetPrevNextMediaInFolderModel extends Model
{

    /**
     * Used for logging information.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * Create a new model instance.
     *
     * @param LoggerInterface $logger logger reference
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Get all media in a folder from the database.
     *
     * @param int                 $folderId       Identification number of the folder, containing the requested media
     * @param MediaReferenceModel $mediaReference media that is being viewed
     * @param string              $sortBy         Column that the media will be sorted by. Must be one of ["individual", "added_date", "created_date"]
     * @param string              $sortOrder      Order by wich the media will be sorted. Must be one of ["asc", "desc"]
     *
     * @return MediaPrevNextFolderModel
     */
    public function get(int $folderId, MediaReferenceModel $mediaReference, string $sortBy, string $sortOrder): MediaPrevNextFolderModel
    {
        $database = new Database($this->logger);
        if (empty($folderId) === false) {
            switch ($sortBy) {
                case "added_date":
                    $dbSortBy = "added";
                    break;
                case "created_date":
                    $dbSortBy = "mediacreationdate";
                    break;
                default:
                    $dbSortBy = "sort";
                    break;
            }
    
            switch ($sortOrder) {
                case "asc":
                    $dbSortOrder = "ASC";
                    break;
                default:
                    $dbSortOrder = "DESC";
                    break;
            }

            $queryBuilder = new QueryBuilderSelect();
            $queryBuilder->select("media_id AS mediaid", "source", "sort");
            $queryBuilder->from("web_lb_folder_media");
            $queryBuilder->andWhereEqualsBool("visible", true);
            $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
            $queryBuilder->sort($dbSortBy." ".$dbSortOrder);
        } else {
            throw new InvalidArgumentException("Either folderId or hash must be set");
        }

        // execute query
        $resultsMediaIds = $database->queryPreparedStatement($queryBuilder);
        $database->close();

        $mediaIds = [];
        $matchKey = null;

        foreach ($resultsMediaIds as $key => $resultMediaId) {
            $temp       = [
                "mediaid" => $resultMediaId->mediaid,
                "source" => $resultMediaId->source
            ];
            $mediaIds[] = $temp;
            
            if ($resultMediaId->mediaid === (string) $mediaReference->id) {
                // print("match");
                $matchKey = $key;
            }
        }
        // print_r($mediaIds);

        if (($matchKey - 1) < 0) {
            $prevMediaId     = 0;
            $prevMediaSource = "";
        } else {
            $prevMediaId     = $mediaIds[($matchKey - 1)]["mediaid"];
            $prevMediaSource = $mediaIds[($matchKey - 1)]["source"];
        }
        
        if (($matchKey + 1) >= count($resultsMediaIds)) {
            $nextMediaId     = 0;
            $nextMediaSource = "";
        } else {
            $nextMediaId     = $mediaIds[($matchKey + 1)]["mediaid"];
            $nextMediaSource = $mediaIds[($matchKey + 1)]["source"];
        }

        return new MediaPrevNextFolderModel(
            $mediaReference,
            $prevMediaId,
            $prevMediaSource,
            $nextMediaId,
            $nextMediaSource
        );
    }


}
