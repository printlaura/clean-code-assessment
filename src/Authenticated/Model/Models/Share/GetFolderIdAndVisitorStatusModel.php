<?php

namespace App\Authenticated\Model\Models\Share;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Visiting\Exceptions\FolderUnknownException;
use UnexpectedValueException;

/**
 * Class for fetching folder id and visitor status using Hash
 */
class GetFolderIdAndVisitorStatusModel extends Model
{


    /**
     * Constructor
     */
    public function __construct()
    {
    }


    /**
     * Get folder ID and visitor status
     *
     * @param string   $hash     hash of the folder
     * @param Database $database database resource ID
     *
     * @return array [int folderId, string visitor]
     * @throws FolderUnknownException
     */
    public static function getFolderIdAndVisitorStatus(string $hash, Database $database): array
    {
        $hash = utf8_encode($hash);

        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("id");
        $queryBuilder->from("web_lb_folder");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereEqualsStr("viewhash", $hash);

        $visitor = 'V';
        try {
            $resultFolder = $database->queryPreparedStatement($queryBuilder, 1);
            $resultFolder = $resultFolder[0];
        } catch (UnexpectedValueException $e) {
            $queryBuilder2 = new QueryBuilderSelect();
            $queryBuilder2->select("id");
            $queryBuilder2->from("web_lb_folder");
            $queryBuilder2->andWhereEqualsBool("visible", true);
            $queryBuilder2->andWhereEqualsStr("edithash", $hash);
            try {
                $resultFolder = $database->queryPreparedStatement($queryBuilder2, 1);
                $resultFolder = $resultFolder[0];
                $visitor      = 'E';
            } catch (UnexpectedValueException $e) {
                throw new FolderUnknownException(0);
            }
        }
        
        return [
            $resultFolder->id,
            $visitor,
        ];
    }


}
