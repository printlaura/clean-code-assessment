<?php

namespace App\Visiting\Model\Models\Description;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Description for folder and media
 */
class DescriptionModel extends Model
{


    /**
     * Get description from folder or media
     *
     * @param LoggerInterface          $logger
     * @param int                      $folderId       folder to get the description from or the media is in
     * @param MediaReferenceModel|null $mediaReference media to get the description from, null when getting description from folder
     *
     * @return string
     */
    public static function get(LoggerInterface $logger, int $folderId, ?MediaReferenceModel $mediaReference = null): string
    {
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("description");
        $queryBuilder->from("web_lb_description");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        if (isset($mediaReference) === true) {
            $queryBuilder->andWhereEqualsStr("object_type", "M");
            $queryBuilder->andWhereEqualsInt("object_id", $mediaReference->id);
            $queryBuilder->andWhereEqualsStr("source", $mediaReference->source);
        } else {
            $queryBuilder->andWhereEqualsStr("object_type", "F");
        }
        // execute query
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($queryBuilder);
        $database->close();
        if (count($results) > 1) {
            throw new UnexpectedValueException("Query to get folder name didn't return 1 or 0 rows, but ".count($results));
        }
        if (count($results) === 0) {
            $logger->debug("no description found");
            $output = "";
        } else {
            $output = utf8_encode($results[0]->description);
        }
        return $output;
    }


    /**
     * Same  as get, but for multiple descriptions
     *
     * @param LoggerInterface $logger
     * @param int[]           $folderIds int array of folder ids
     *
     * @return array dictionary with folder id as key and description as value
     */
    public static function getMultipleFolderDescriptions(LoggerInterface $logger, array $folderIds): array
    {
        if (count($folderIds) === 0) {
            return [];
        }
        // build query
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("folder_id AS folderid", "description");
        $queryBuilder->from("web_lb_description");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereIsInIntArray("folder_id", $folderIds);
        $queryBuilder->andWhereEqualsStr("object_type", "F");
        $queryBuilder->sort("folder_id");
        // execute query
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($queryBuilder);
        $database->close();

        $dict = [];
        // add descriptions to dict
        foreach ($results as $result) {
            $dict[(int) $result->folderid] = $result->description;
        }
        // set default description to ""
        foreach ($folderIds as $folderId) {
            if (isset($dict[$folderId]) === false) {
                $dict[$folderId] = "";
            }
        }
        return $dict;
    }


}
