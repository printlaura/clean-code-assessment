<?php

namespace App\Visiting\Model\Models\Media;

use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaInfoModel;
use App\Unauthenticated\Model\Models\Media\MediaModel;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Unauthenticated\Model\Models\Media\Pond5Model;
use App\Visiting\Model\Models\Comment\ShowCommentsModel;
use App\Visiting\Model\Models\Description\DescriptionModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Class for getting all media in a folder
 */
class GetAllMediaInFolderModel extends Model
{


    /**
     * Get all media in a folder from the database.
     *
     * @param LoggerInterface $logger    logger
     * @param int             $folderId  Identification number of the folder, containing the requested media
     * @param int             $limit     Maximum amount of media that will be returned.
     * @param int             $offset    Amount of media to skip.
     * @param string          $sortBy    Column that the media will be sorted by. Must be one of ["individual", "added_date", "created_date"]
     * @param string          $sortOrder Order by wich the media will be sorted. Must be one of ["asc", "desc"]
     * @param string          $language  de or en
     *
     * @return MediaModel[]
     * @throws InvalidArgumentException on wrong parameter
     * @throws MediaSourceUnknownException
     * @throws LicenseUnknownException
     * @throws MediaUnknownException
     */
    public static function getAllMediaInFolder(LoggerInterface $logger, int $folderId, int $limit, int $offset, string $sortBy, string $sortOrder, string $language): array
    {

        if ($sortBy === "added_date") {
            $dbSortBy = "added";
        } else if ($sortBy === "created_date") {
            $dbSortBy = "mediacreationdate";
        } else {
            $dbSortBy = "sort";
        }

        if ($sortOrder === "asc") {
            $dbSortOrder = "ASC";
        } else {
            $dbSortOrder = "DESC";
        }

        $database     = new Database($logger);
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("media_id AS mediaid", "source", "type", "sort");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->sort($dbSortBy." ".$dbSortOrder);
        $queryBuilder->limitOffset($limit, $offset);

        // execute query
        $resultsMediaIds = $database->queryPreparedStatement($queryBuilder);
        $database->close();

        $mediaArray = [];
        foreach ($resultsMediaIds as $resultMediaId) {
            $sort           = intval($resultMediaId->sort);
            $mediaReference = new MediaReferenceModel(
                $resultMediaId->source,
                $resultMediaId->mediaid,
                $resultMediaId->type
            );

            if ($mediaReference->source === 'pond5') {
                $media = self::getMediaPond5($logger, $mediaReference, $folderId, $sort);
            } else if ($mediaReference->source === "st" || $mediaReference->source === "sp") {
                $media = self::getMediaStSp($logger, $language, $mediaReference, $folderId, $sort);
            } else {
                throw new MediaSourceUnknownException($resultMediaId->source);
            }

            $mediaArray[] = $media;
        }
        return $mediaArray;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int $mediaId id of the media
     *
     * @return QueryBuilderSelect
     */
    private static function generateStockMediaSql(int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.prosa AS prosa",
            "bilder.hoehe AS height",
            "bilder.breite AS width",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup AS licencegroup",
            "bilder.datum AS datum",
            "bilder_erw.master_image AS masterimage",
            "bilder.fotografen AS fotografen"
        );
        $queryBuilder->from("bilder_erw, bilder");
        $queryBuilder->andWhereEqualsInt("bilder.bildnummer", $mediaId);
        $queryBuilder->andWhereEqualsFunc("bilder_erw.bildnummer", "bilder.bildnummer");

        return $queryBuilder;
    }


    /**
     * Manage the generation of the SQL
     *
     * @param int $mediaId id of the media
     *
     * @return QueryBuilderSelect
     */
    private static function generateSportMediaSql(int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.Bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.Bildbeschreibung AS prosa",
            "bilder_erw.hoehe AS height",
            "bilder_erw.breite AS width",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup AS licencegroup",
            "bilder.Datum AS datum",
            "bilder_erw.master_image AS masterimage",
            "stammdaten.name AS fotografen"
        );

        $queryBuilder->from(
            "bilder
            INNER JOIN bilder_erw ON bilder.Bildnummer = bilder_erw.bildnummer
            INNER JOIN fotografen ON bilder.Lieferanten = fotografen.id
            INNER JOIN stammdaten ON stammdaten.id = fotografen.bezug"
        );

        $queryBuilder->andWhereEqualsInt("bilder.Bildnummer", $mediaId);

        return $queryBuilder;
    }


    /**
     * Get Media from Pond5 source using database
     *
     * @param LoggerInterface     $logger
     * @param MediaReferenceModel $mediaReference
     * @param int                 $folderId
     * @param int                 $sort
     *
     * @return MediaModel
     * @throws LicenseUnknownException
     * @throws MediaUnknownException
     */
    private static function getMediaPond5(LoggerInterface $logger, MediaReferenceModel $mediaReference, int $folderId, int $sort): MediaModel
    {
        $mediaModel = Pond5Model::getMediaModel($logger, $mediaReference->id);

        // get comment count
        $showCommentsModel = new ShowCommentsModel($logger);
        $mediaCommentCount = $showCommentsModel->getMediaCommentCount($folderId, $mediaReference);
        $mediaModel->setCommentCount($mediaCommentCount);

        // get description
        $mediaDescription = DescriptionModel::get($logger, $folderId, $mediaReference);
        $mediaModel->setNotice($mediaDescription);

        $mediaModel->setSort($sort);
        return $mediaModel;
    }


    /**
     * Get Media from Stock or Sport database
     *
     * @param LoggerInterface     $logger
     * @param string              $language
     * @param MediaReferenceModel $mediaReference
     * @param int                 $folderId
     * @param int                 $sort
     *
     * @return MediaModel
     * @throws LicenseUnknownException
     * @throws MediaUnknownException when media does not exist
     */
    private static function getMediaStSp(LoggerInterface $logger, string $language, MediaReferenceModel $mediaReference, int $folderId, int $sort): MediaModel
    {
        if ($mediaReference->source === 'st') {
            // execute query
            $sqlCommand = self::generateStockMediaSql($mediaReference->id);
            $database   = new Database($logger, "st");
        } else {
            // execute query
            $sqlCommand = self::generateSportMediaSql($mediaReference->id);
            $database   = new Database($logger, "sp");
        }
        try {
            $resultMedia = $database->queryPreparedStatement($sqlCommand, 1)[0];
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new MediaUnknownException($mediaReference);
        }
        $database->close();

        if ($language === 'en') {
            $mediaTitle = $resultMedia->captionshorten;
        } else {
            $mediaTitle = $resultMedia->captionshortde;
        }

        $mediaCaption      = $resultMedia->prosa;
        $mediaClipLength   = $resultMedia->cliplength;
        $mediaAuthor       = $resultMedia->fotografen;
        $mediaCreationDate = new CustomDateTimeModel($resultMedia->datum);

        // get license
        list($mediaLicense, $mediaLicenseLanguagePointer, $mediaUsageLicenses) = MediaInfoModel::getLicense($logger, $resultMedia->licencegroup);

        // get urls
        $mediaThumb = $mediaReference->getThumb($language);
        $mediaSrc   = $mediaReference->getSrc($language);
        $mediaPrev  = $mediaReference->getPrev($language);

        // get comment count
        $showCommentsModel = new ShowCommentsModel($logger);
        $mediaCommentCount = $showCommentsModel->getMediaCommentCount($folderId, $mediaReference);

        // get description
        $mediaDescription = DescriptionModel::get($logger, $folderId, $mediaReference);

        return new MediaModel(
            $mediaReference,
            $mediaThumb,
            $mediaSrc,
            $sort,
            $mediaTitle,
            $mediaCaption,
            $mediaCommentCount,
            $mediaDescription,
            (int) $resultMedia->height,
            (int) $resultMedia->width,
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $mediaUsageLicenses,
            $mediaAuthor,
            (empty($mediaClipLength) === true ? "" : $mediaClipLength),
            $mediaCreationDate,
            null,
            $mediaPrev
        );
    }


    /**
     * Returns the total amount of media in a folder.
     *
     * @param LoggerInterface $logger
     * @param int             $folderId
     *
     * @return int
     */
    public static function getMediaCount(LoggerInterface $logger, int $folderId): int
    {
        $database     = new Database($logger);
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("count(media_id) AS mediacount");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // execute query
        $resultsMediaIds = $database->queryPreparedStatement($queryBuilder, 1)[0];
        $database->close();
        return (int) $resultsMediaIds->mediacount;
    }


    /**
     * Checks if requested media is present in a folder.
     *
     * @param LoggerInterface     $logger
     * @param int                 $folderId
     * @param MediaReferenceModel $mediaReference
     *
     * @return void
     * @throws MediaNotInFolderException
     */
    public static function assertMediaExistsInFolder(LoggerInterface $logger, int $folderId, MediaReferenceModel $mediaReference)
    {
        $database     = new Database($logger);
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("count(media_id) AS mediacount");
        $queryBuilder->from("web_lb_folder_media");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsInt("media_id", $mediaReference->id);
        $queryBuilder->andWhereEqualsBool("visible", true);
        // execute query
        $resultsMediaId = $database->queryPreparedStatement($queryBuilder, 1)[0];
        $database->close();
        if ($resultsMediaId->mediacount < 1) {
            throw new MediaNotInFolderException($mediaReference);
        }
    }


}
