<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Returns similar media for one media as previews
 */
class SimilarMediaModel extends Model
{
    const SIMILAR_MEDIA_EXAMPLE_RESPONSE = true;


    /**
     * Returns array of previews for similar media. Currently, only responds with one example media.
     *
     * @param MediaReferenceModel $mediaReference media that similar medias will get returned for
     * @param string              $language       "de" or "en"
     * @param LoggerInterface     $logger
     *
     * @return PreviewModel[]
     * @throws LicenseUnknownException
     * @throws MediaSourceUnknownException
     */
    public static function get(MediaReferenceModel $mediaReference, string $language, LoggerInterface $logger): array
    {
        if (self::SIMILAR_MEDIA_EXAMPLE_RESPONSE === true) {
            $mediaRefExamples = [
                new MediaReferenceModel("st", 139832477, "image"),
                new MediaReferenceModel("sp", 21509634, "image"),
                new MediaReferenceModel("st", 99156325, "image"),
                new MediaReferenceModel("st", 145051675, "image"),
                new MediaReferenceModel("st", 139832477, "image"),
                new MediaReferenceModel("st", 130440363, "video"),
                new MediaReferenceModel("sp", 1010255576, "video"),
                new MediaReferenceModel("st", 148880846, "video"),
                new MediaReferenceModel("pond5", 53614322, "video"),
            ];
            // It's very important to set withSimilarMedia here to false, otherwise this will lead to an infinite loop.
            return MediaInfoModel::getSimilarMediaInfos($logger, $mediaRefExamples, $language);
        } else {
            return self::getMedia($mediaReference, $language, $logger);
        }
    }


    /**
     * Read all similar medias from the DB
     *
     * @param MediaReferenceModel $mediaReference media that is being viewed
     * @param string              $language       de or en
     * @param LoggerInterface     $logger
     *
     * @return PreviewModel[] returns previews or empty array
     */
    private static function getMedia(MediaReferenceModel $mediaReference, string $language, LoggerInterface $logger): array
    {
        $sqlMetaPhone = self::generateMetaPhoneSql($mediaReference->id);
        $database     = new Database($logger, $mediaReference->source);
        $results      = $database->queryPreparedStatement($sqlMetaPhone);
        $metaPhone    = $results[0]->metaphone;
        $database->close();

        if (isset($metaPhone) === false) {
            $logger->warning("No metaphone available for $mediaReference, returning empty array [].");
            return [];
        }

        return self::getPreviews($database, $metaPhone, $mediaReference->id, $language, $logger);
    }


    /**
     * Get the previews of the pictures
     *
     * @param Database        $database  database connection
     * @param string          $metaPhone similar hash
     * @param int             $mediaId   media ID
     * @param string          $language  'en' or default (de)
     * @param LoggerInterface $logger
     *
     * @return PreviewModel[] previews
     */
    private static function getPreviews(Database $database, string $metaPhone, int $mediaId, string $language, LoggerInterface $logger): array
    {
        $resultMedias = $database->queryPreparedStatement(self::getSimilarMediaSql($metaPhone, $mediaId));
        $previews     = [];

        if (count($resultMedias) > 0) {
            foreach ($resultMedias as $resultMedia) {
                $mediaReference = new MediaReferenceModel($resultMedia->source, $resultMedia->mediaid);

                $mediaThumb = $mediaReference->getThumb($language);
                $mediaSrc   = $mediaReference->getSrc($language);

                try {
                    $preview    = new PreviewModel(
                        $mediaReference,
                        $mediaThumb,
                        $mediaSrc,
                        ($resultMedia->masterimage === 1 ? 1 : 0)
                    );
                    $previews[] = $preview;
                } catch (InvalidArgumentException $exception) {
                    $logger->warning("Failed to construct Preview from database. Skipped Preview for this object.", [$exception]);
                }
            }
        }

        return $previews;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int $mediaId media id in the DB
     *
     * @return QueryBuilderSelect
     */
    private static function generateMetaPhoneSql(int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "metaphone AS metaphone",
            "master_image AS masterimage"
        );
        $queryBuilder->from("bilder_erw");
        $queryBuilder->andWhereEqualsInt("bildnummer", $mediaId);

        return $queryBuilder;
    }


    /**
     * Read media SQL from the database with sort options
     *
     * @param string $metaPhone folder ID
     * @param int    $mediaId   media ID
     *
     * @return QueryBuilderSelect
     */
    private static function getSimilarMediaSql(string $metaPhone, int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bildnummer AS mediaid",
            "master_image AS masterimage"
        );
        $queryBuilder->from("bilder_erw");
        $queryBuilder->andWhereEqualsStr("metaphone", $metaPhone);
        $queryBuilder->andWhereEqualsInt("bildnummer", $mediaId, false);
        $queryBuilder->sort("bildnummer ASC");
        $queryBuilder->limitOffset(4);
        return $queryBuilder;
    }


}
