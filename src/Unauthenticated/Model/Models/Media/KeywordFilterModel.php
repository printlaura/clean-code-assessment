<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use Psr\Log\LoggerInterface;

/**
 * Filters keywords, to only return keywords that are in the suchbegriffe table.
 */
class KeywordFilterModel extends Model
{


    /**
     * Returns allowed keywords from a string.
     *
     * @param string          $keywords seperated by a comma
     * @param LoggerInterface $logger
     * @param string          $language "en" or "de"
     *
     * @return string komma seperated list of filtered keywords
     */
    public static function getFromString(string $keywords, LoggerInterface $logger, string $language = "en"): string
    {
        if (strlen($keywords) === 0) {
            return $keywords;
        }
        if (str_contains($keywords, ",") === false) {
            return $keywords;
        }
        $keywords = iconv("cp1252", "UTF-8", $keywords);
        return self::get(explode(",", $keywords), $logger, $language);
    }


    /**
     * Returns allowed keywords, seperated by a komma.
     *
     * @param array           $keywords
     * @param LoggerInterface $logger
     * @param string          $language "en" or "de" default is "en"
     *
     * @return string komma seperated list of filtered keywords
     */
    public static function get(array $keywords, LoggerInterface $logger, string $language = "en"): string
    {
        if (count($keywords) === 0) {
            return "";
        }
        $keywords = array_map('trim', $keywords);

        $query = new QueryBuilderSelect();
        $query->select("suchbegriff AS keyword");
        if ($language === "de") {
            $query->from("sitemaps_suchbegriffe");
        } else {
            $query->from("sitemaps_suchbegriffe_com");
        }
        $query->andWhereIsInStringArray("suchbegriff", $keywords);

        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($query);
        if (count($results) === 0) {
            return "";
        }
        $filteredKeywords = [];
        foreach ($results as $result) {
            $filteredKeywords[] = $result->keyword;
        }
        return implode(",", $filteredKeywords);
    }


}
