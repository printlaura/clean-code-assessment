<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Contains detail information on a media.
 */
class MediaInfoModel extends Model
{

    /**
     * Variable for the id
     *
     * @var integer
     */
    public int $mediaid;
    
    /**
     * Variable for the mediaThumb
     *
     * @var string
     */
    public string $mediathumb;
    
    /**
     * Variable for the mediaSrc
     *
     * @var string
     */
    public string $mediasrc;
    
    /**
     * Variable for the mediaPrev
     *
     * @var string|null
     */
    public ?string $mediaprev;
    
    /**
     * Variable for the mediaType
     *
     * @var string
     */
    public string $mediatype;
    
    /**
     * Variable for the source
     *
     * @var string
     */
    public string $source;

    /**
     * Variable for the creator
     *
     * @var string
     */
    public string $creator;
    
    /**
     * Variable for the height
     *
     * @var integer
     */
    public int $height;
    
    /**
     * Variable for the width
     *
     * @var integer
     */
    public int $width;
    
    /**
     * Variable for the creationDate
     *
     * @var CustomDateTimeModel
     */
    public CustomDateTimeModel $creationdate;
    
    /**
     * Variable for the clipLength
     *
     * @var integer
     */
    public int $cliplength;

    /**
     * Variable for the master image flagg
     *
     * @var int|null
     */
    public ?int $masterimage;

    /**
     * Variable for the title
     *
     * @var string|null
     */
    public ?string $title;

    /**
     * Variable for the caption
     *
     * @var string
     */
    public string $caption;

    /**
     * Variable for the licenseType
     *
     * @var string
     */
    public string $licensetype;

    /**
     * Variable for the licenceLanguagePointer
     *
     * @var string
     */
    public string $licencelanguagepointer;
    
    /**
     * Variable for the keywords
     *
     * @var string
     */
    public string $keywords;
    
    /**
     * Variable for the categories
     *
     * @var string
     */
    public string $categories;
    
    /**
     * Variable for the territoryRestrictions
     *
     * @var string|null
     */
    public ?string $territoryrestrictions;
    
    /**
     * Variable for the usageLicences
     *
     * @var UsageLicensesModel[]
     */
    public array $usagelicences;

    /**
     * Shows similar media to the one viewing right now like in search.
     *
     * @var MediaInfoModel[] similar media infos
     */
    public ?array $similarmedia;

    private MediaReferenceModel $mediaReference;


    /**
     * Constructor define the MediaInfo model
     *
     * @param MediaReferenceModel  $mediaReference         unique reference to media
     * @param string               $mediaThumb             link to the media thumbnail
     * @param string               $mediaSrc               link to the media source
     * @param string               $creator                creator of this media
     * @param int                  $height                 height of this media
     * @param int                  $width                  width of this media
     * @param CustomDateTimeModel  $creationDate           datetime of media creation
     * @param int                  $clipLength             price of the media
     * @param int|null             $masterImage            if this media is a master image or not
     * @param string|null          $title                  title for the media
     * @param string               $caption                caption for the media
     * @param string               $licenseType            license type of the media
     * @param string               $licenceLanguagePointer license language pointer to json in react
     * @param string               $keywords               keywords for this media
     * @param array                $categories             categories for this media
     * @param string|null          $territoryRestrictions  territory restrictions for this media
     * @param UsageLicensesModel[] $usageLicences          usage licenses
     * @param PreviewModel[]       $similarMedia
     * @param string|null          $mediaPrev              link to the media preview, only when $type is "image"
     */
    public function __construct(
        MediaReferenceModel $mediaReference,
        string              $mediaThumb,
        string              $mediaSrc,
        string              $creator,
        int                 $height,
        int                 $width,
        CustomDateTimeModel $creationDate,
        int     $clipLength,
        ?int    $masterImage,
        ?string $title,
        string  $caption,
        string  $licenseType,
        string  $licenceLanguagePointer,
        string  $keywords,
        array   $categories,
        ?string $territoryRestrictions,
        array   $usageLicences,
        ?array  $similarMedia = null,
        ?string $mediaPrev = null
    ) {
        $this->mediaReference = $mediaReference;
        $this->mediaid        = $mediaReference->id;
        $this->mediathumb     = $mediaThumb;
        $this->mediasrc       = $mediaSrc;
        $mediaReference->assertType();
        $this->mediatype = $mediaReference->type;
        if ($mediaReference->isImage() === false) {
            $this->mediaprev = $mediaPrev;
        } else {
            $this->mediaprev = null;
        }
        $this->source       = $mediaReference->source;
        $this->creator      = $creator;
        $this->height       = $height;
        $this->width        = $width;
        $this->creationdate = $creationDate;
        $this->cliplength   = $clipLength;
        $this->masterimage  = $masterImage;
        $this->title        = utf8_encode($title);
        $this->caption      = utf8_encode($caption);
        $this->licensetype  = $licenseType;
        $this->licencelanguagepointer = $licenceLanguagePointer;
        $this->keywords   = $keywords;
        $this->categories = implode(", ", $categories);
        $this->territoryrestrictions = $territoryRestrictions;
        $this->usagelicences         = $usageLicences;
        $this->similarmedia          = $similarMedia;
    }


    /**
     * Manage the generation of the query to fetch single media by mediaId from Sport db
     *
     * @param int $mediaId id of the media
     *
     * @return QueryBuilderSelect
     */
    public static function generateSportMediaSql(int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.Bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.Bildbeschreibung AS prosa",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup",
            "bilder.Datum AS datum",
            "stammdaten.name AS fotografen",
            "bilder_erw.hoehe AS hoehe",
            "bilder_erw.breite AS breite",
            "bilder.mp_namen AS keywords",
            "bilder_erw.category_id AS category",
            "bilder_erw.master_image AS masterimage",
            "stammdaten.restriktionen AS restrictions",
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
     * Manage the generation of the query to fetch Licence by licenceGroupId
     *
     * @param int $licenseGroupId id of the license group
     *
     * @return QueryBuilderSelect
     */
    public static function generateLicenseSql(int $licenseGroupId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "licencegroup.languagepointer AS licegrouplanguagepointer",
            "licencegroup.shortname AS licegroupshortname",
            "licence.languagepointer AS licelanguagepointer",
            "licence.shortname AS liceshortname",
            "licence.credits AS credits",
            "licence.price AS price",
            "licence.currency AS currency"
        );
        $queryBuilder->from(
            "licence 
            INNER JOIN licencegroup_licence ON licence.id=licencegroup_licence.licence 
            INNER JOIN licencegroup ON licencegroup_licence.licencegroup=licencegroup.id"
        );
        $queryBuilder->andWhereEqualsInt("licencegroup.id", $licenseGroupId);
        $queryBuilder->andWhereEqualsFunc("licencegroup.active", "1");
        $queryBuilder->andWhereEqualsFunc("licence.active", "1");

        return $queryBuilder;
    }


    /**
     * Get similar media infos for all types of media from referenceModels array
     *
     * @param LoggerInterface       $logger
     * @param MediaReferenceModel[] $mediaReferences
     * @param string                $language
     *
     * @return array          array with MediaInfoModels of the media in $mediaIds
     * @throws LicenseUnknownException
     * @throws MediaSourceUnknownException
     */
    public static function getSimilarMediaInfos(LoggerInterface $logger, array $mediaReferences, string $language): array
    {
        $spMediaReferences   = [];
        $stMediaReferences   = [];
        $pondMediaReferences = [];
        // separate media by source
        foreach ($mediaReferences as $mediaReference) {
            if ($mediaReference->source === 'st') {
                $stMediaReferences[] = $mediaReference;
            } else if ($mediaReference->source === 'sp') {
                $spMediaReferences[] = $mediaReference;
            } else if ($mediaReference->source === 'pond5') {
                $pondMediaReferences[] = $mediaReference;
            } else {
                throw new UnexpectedValueException("Unknown source: ".$mediaReference->source);
            }
        }
        // get models for each source
        $stMediaInfos   = self::getSimilarMediaInfosStSp($logger, $stMediaReferences, $language, "st");
        $spMediaInfos   = self::getSimilarMediaInfosStSp($logger, $spMediaReferences, $language, "sp");
        $pondMediaInfos = Pond5Model::getSimilarMediaInfos($logger, $pondMediaReferences, $language);
        $mediaInfos     = array_merge($stMediaInfos, $spMediaInfos, $pondMediaInfos);
        // sort the media infos in the order of the media refences received
        $mediaInfosSorted = [];
        foreach ($mediaReferences as $mediaReference) {
            foreach ($mediaInfos as $mediaInfo) {
                if ($mediaReference->equals($mediaInfo->getMediaReference()) === true) {
                    $mediaInfosSorted[] = $mediaInfo;
                    break;
                }
            }
        }
        return $mediaInfosSorted;
    }


    /**
     * Returns media reference model
     *
     * @return MediaReferenceModel
     */
    public function getMediaReference(): MediaReferenceModel
    {
        return $this->mediaReference;
    }


    /**
     * Same as get, but for multiple media items
     *
     * @param LoggerInterface       $logger
     * @param MediaReferenceModel[] $mediaReferences array of MediaReferenceModels
     * @param string                $language
     * @param string                $mediaSource     "st" or "sp" depending on the source
     *
     * @return MediaInfoModel[]     array with MediaInfoModels of the media in $mediaIds
     * @throws LicenseUnknownException
     */
    public static function getSimilarMediaInfosStSp(LoggerInterface $logger, array $mediaReferences, string $language, string $mediaSource): array
    {
        // get ids from references
        $mediaIds = array_column($mediaReferences, "id");
        // get all mediaInfos at once
        if ($mediaSource === 'st') {
            $queryBuilderSelect = self::generateMultipleStockMediaSql($mediaIds);
            $database           = new Database($logger, "st");
        } else {
            $queryBuilderSelect = self::generateMultipleSportMediaSql($mediaIds);
            $database           = new Database($logger, "sp");
        }

        $resultMedias = $database->queryPreparedStatement($queryBuilderSelect);
        $database->close();

        $dict = [];
        // add MediaInfoModela to dict
        foreach ($resultMedias as $resultMedia) {
            $mediaReference = new MediaReferenceModel($mediaSource, $resultMedia->bildnummer);
            // set type
            if ($resultMedia->mediatype === '3') {
                $mediaReference->setType('V');
            } else {
                $mediaReference->setType('I');
            }
            // license
            list($mediaLicense, $mediaLicenseLanguagePointer, $mediaUsageLicenses) = self::getLicense($logger, $resultMedia->licencegroup);
            $logger->debug("got licence");
            // restrictions
            try {
                $restrictionsDatabaseString = $resultMedia->restrictions;
                $mediaTypeTranslated        = $mediaReference->getMediaTypeInLanguage($mediaReference->getTypeAsIV(), $language);
                $mediaRestrictions          = TransformCountyRestrictionsModel::buildRestrictionsSentenceFromDatabaseString(
                    $language,
                    $restrictionsDatabaseString,
                    $mediaTypeTranslated
                );
                $logger->debug("got mediaRestrictions");
            } catch (UnexpectedValueException $unexpectedValueException) {
                $logger->warning($unexpectedValueException);
                $mediaRestrictions = null;
            }
            // caption
            if ($language === 'en') {
                $mediaTitle = $resultMedia->captionshorten;
            } else {
                $mediaTitle = $resultMedia->captionshortde;
            }
            if (strlen($mediaTitle) < 1) {
                $mediaTitle = trim(substr($resultMedia->prosa, 0, 100));
            }

            $similarMedia = null;
            $keywords     = "";

            // build media info
            $mediaInfoModel = new MediaInfoModel(
                $mediaReference,
                $mediaReference->getThumb($language),
                $mediaReference->getSrc($language),
                $resultMedia->fotografen,
                self::getIntIfItsNullReturnDefault($resultMedia->hoehe, 0),
                self::getIntIfItsNullReturnDefault($resultMedia->breite, 0),
                new CustomDateTimeModel($resultMedia->datum),
                self::getIntIfItsNullReturnDefault($resultMedia->cliplength, 0),
                $resultMedia->masterimage,
                $mediaTitle,
                $resultMedia->prosa,
                $mediaLicense,
                $mediaLicenseLanguagePointer,
                $keywords,
                self::getCategoryFromDatabase($logger, $resultMedia->category),
                $mediaRestrictions,
                $mediaUsageLicenses,
                $similarMedia,
                $mediaReference->getPrev($language)
            );
            $dict[]         = $mediaInfoModel;
        }

        return $dict;
    }


    /**
     * Manage the generation of the query to fetch multiple media from Sport db
     *
     * @param int[] $mediaIds int array of media ids
     *
     * @return QueryBuilderSelect
     */
    public static function generateMultipleSportMediaSql(array $mediaIds): QueryBuilderSelect
    {
        if (count($mediaIds) === 0) {
            throw new InvalidArgumentException("Mediaids array must contain at least one item.");
        }
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.Bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.Bildbeschreibung AS prosa",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup",
            "bilder.Datum AS datum",
            "stammdaten.name AS fotografen",
            "bilder_erw.hoehe AS hoehe",
            "bilder_erw.breite AS breite",
            "bilder.mp_namen AS keywords",
            "bilder_erw.category_id AS category",
            "bilder_erw.master_image AS masterimage",
            "stammdaten.restriktionen AS restrictions",
        );
        $queryBuilder->from(
            "bilder
            INNER JOIN bilder_erw ON bilder.Bildnummer = bilder_erw.bildnummer
            INNER JOIN fotografen ON bilder.Lieferanten = fotografen.id
            INNER JOIN stammdaten ON stammdaten.id = fotografen.bezug"
        );
        $queryBuilder->andWhereIsInIntArray("bilder.bildnummer", $mediaIds);
        return $queryBuilder;
    }


    /**
     * Manage the generation of a query to Stock db to fetch multiple media wth ids from mediaIds array
     *
     * @param int[] $mediaIds int array of media ids
     *
     * @return QueryBuilderSelect
     */
    public static function generateMultipleStockMediaSql(array $mediaIds): QueryBuilderSelect
    {
        if (count($mediaIds) === 0) {
            throw new InvalidArgumentException("Mediaids array must contain at least one item.");
        }
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.prosa AS prosa",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup AS licencegroup",
            "bilder.datum AS datum",
            "bilder.fotografen AS fotografen",
            "bilder.hoehe AS hoehe",
            "bilder.breite AS breite",
            "bilder.suchtext AS keywords",
            "bilder_erw.category_id AS category",
            "bilder_erw.master_image AS masterimage",
            "fotografen.restriktionen AS restrictions"
        );
        $queryBuilder->from("bilder_erw, bilder, fotografen");
        $queryBuilder->andWhereIsInIntArray("bilder.bildnummer", $mediaIds);
        $queryBuilder->andWhereEqualsFunc("bilder_erw.bildnummer", "bilder.bildnummer");
        $queryBuilder->andWhereEqualsFunc("fotografen.name", "bilder.fotografen");
        return $queryBuilder;
    }


    /**
     * Generate a query to Stock db to fetch a single media by mediaId
     *
     * @param int $mediaId id of the media
     *
     * @return QueryBuilderSelect
     */
    public static function generateStockMediaSql(int $mediaId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select(
            "bilder.bildnummer AS bildnummer",
            "bilder_erw.mediatype AS mediatype",
            "bilder_erw.caption_short_de AS captionshortde",
            "bilder_erw.caption_short_en AS captionshorten",
            "bilder.prosa AS prosa",
            "bilder_erw.clip_length_s AS cliplength",
            "bilder_erw.licencegroup AS licencegroup",
            "bilder.datum AS datum",
            "bilder.fotografen AS fotografen",
            "bilder.hoehe AS hoehe",
            "bilder.breite AS breite",
            "bilder.suchtext AS keywords",
            "bilder_erw.category_id AS category",
            "bilder_erw.master_image AS masterimage",
            "fotografen.restriktionen AS restrictions"
        );
        $queryBuilder->from("bilder_erw, bilder, fotografen");
        $queryBuilder->andWhereEqualsInt("bilder.bildnummer", $mediaId);
        $queryBuilder->andWhereEqualsFunc("bilder_erw.bildnummer", "bilder.bildnummer");
        $queryBuilder->andWhereEqualsFunc("fotografen.name", "bilder.fotografen");

        return $queryBuilder;
    }


    /**
     * Convert the clip length from 0:44 to only seconds 44
     *
     * @param string $clipLength clip length
     *
     * @return int
     */
    public static function convertClipLength(string $clipLength): int
    {
        $timeSplit = explode(":", $clipLength);
        if ($timeSplit[0] > 0) {
            $duration = (($timeSplit[0] * 60) + $timeSplit[1]);
        } else {
            $duration = $timeSplit[1];
        }
        return $duration;
    }


    /**
     * Get information on Media
     *
     * @param LoggerInterface     $logger           logger
     * @param MediaReferenceModel $mediaReference   media to get information on
     * @param string              $language         de or en
     * @param bool                $withSimilarMedia true to get MediaInfoModel with similar media. Default is false to prevent accidental infinite loops.
     *
     * @return MediaInfoModel
     * @throws LicenseUnknownException
     * @throws MediaSourceUnknownException
     */
    public static function get(LoggerInterface $logger, MediaReferenceModel $mediaReference, string $language, bool $withSimilarMedia = false): MediaInfoModel
    {
        if ($mediaReference->source === 'pond5') {
            return Pond5Model::getMediaInfo($logger, $mediaReference, $language, $withSimilarMedia);
        } else if ($mediaReference->source === "st" || $mediaReference->source === "sp") {
            return self::getMediaInfoStSp($logger, $mediaReference, $language, $withSimilarMedia);
        } else {
            throw new MediaSourceUnknownException($mediaReference->source);
        }
    }


    /**
     * Get media category keys from database
     *
     * @param LoggerInterface $logger
     * @param $category
     *
     * @return array
     */
    public static function getCategoryFromDatabase(LoggerInterface $logger, $category): array
    {
        if (empty($category) === false) {
            if (strpos($category, " ") !== false) {
                $mediaCategoryIds = explode(" ", $category);
            } else {
                $mediaCategoryIds = [$category];
            }
            $queryBuilderCategory = new QueryBuilderSelect();
            $queryBuilderCategory->select("name");
            $queryBuilderCategory->from("category");
            $queryBuilderCategory->andWhereIsInIntArray("id", $mediaCategoryIds);
            $queryBuilderCategory->andWhereEqualsInt("active_flag", 1);

            $databaseCategory = new Database($logger);
            $resultCategory   = $databaseCategory->queryPreparedStatement($queryBuilderCategory);
            $databaseCategory->close();

            $mediaCategory = [];
            foreach ($resultCategory as $result) {
                $mediaCategory[] = $result->name;
            }
            return $mediaCategory;
        }
        return [];
    }


    /**
     * Returns vaule when it's not null, otherwise returns default value
     *
     * @param int|null $value
     * @param int      $default
     *
     * @return int
     */
    public static function getIntIfItsNullReturnDefault(?int $value, int $default): int
    {
        if (isset($value) === true) {
            return $value;
        }
        return $default;
    }


    /**
     * Returns License Information
     *
     * @param LoggerInterface $logger         logger
     * @param int|null        $licenseGroupId license group to get information on, default=1
     *
     * @return array
     * @throws LicenseUnknownException
     */
    public static function getLicense(LoggerInterface $logger, ?int $licenseGroupId = 1): array
    {
        $mediaUsageLicenses = [];
        $mediaLicense       = null;
        $mediaLicenseLanguagePointer = null;
        if (isset($licenseGroupId) === false) {
            $licenseGroupId = 1;
        }
        $sqlLicense = self::generateLicenseSql($licenseGroupId);

        $databaseLicense = new Database($logger);
        $resultLicenses  = $databaseLicense->queryPreparedStatement($sqlLicense);
        $databaseLicense->close();

        foreach ($resultLicenses as $resultLicense) {
            $mediaLicense = $resultLicense->licegroupshortname;
            $mediaLicenseLanguagePointer = $resultLicense->licegrouplanguagepointer;
            $mediaUsageLicense           = new UsageLicensesModel(
                $resultLicense->licelanguagepointer,
                $resultLicense->liceshortname,
                $resultLicense->credits,
                $resultLicense->price,
                $resultLicense->currency
            );
            $mediaUsageLicenses[]        = $mediaUsageLicense;
        }

        if (isset($mediaUsageLicenses) === false) {
            throw new LicenseUnknownException();
        }
        if (count($mediaUsageLicenses) === 0) {
            throw new LicenseUnknownException();
        }
        return [
            utf8_encode($mediaLicense),
            utf8_encode($mediaLicenseLanguagePointer),
            $mediaUsageLicenses,
        ];
    }


    /**
     * Get teritory restrictions from the database
     *
     * @param LoggerInterface     $logger
     * @param MediaReferenceModel $mediaReference
     * @param string              $language       en or de
     *
     * @return string
     */
    public static function getTeritoryRestrictionsFromDatabase(LoggerInterface $logger, MediaReferenceModel $mediaReference, string $language): string
    {
        $queryBuilderRestrictions = new QueryBuilderSelect();
        $queryBuilderRestrictions->select("fotografen.restriktionen AS restrictions");
        $queryBuilderRestrictions->from("fotografen");
        $queryBuilderRestrictions->andWhereEqualsStr("name", "Pond5");
        $database           = new Database($logger, "st");
        $resultRestrictions = $database->queryPreparedStatement($queryBuilderRestrictions);
        $restrictions       = $resultRestrictions[0]->restrictions;
        $database->close();

        $mediaTypeInLanguage = $mediaReference->getMediaTypeInLanguage($mediaReference->getTypeAsIV(), $language);
        return TransformCountyRestrictionsModel::buildRestrictionsSentenceFromDatabaseString(
            $language,
            $restrictions,
            $mediaTypeInLanguage
        );
    }


    /**
     * Get Media Info for stock or sport source
     *
     * @param LoggerInterface     $logger
     * @param MediaReferenceModel $mediaReference
     * @param string              $language         en or de
     * @param bool                $withSimilarMedia true to get MediaInfoModel with similar media
     *
     * @return MediaInfoModel
     * @throws LicenseUnknownException
     * @throws MediaSourceUnknownException
     */
    public static function getMediaInfoStSp(LoggerInterface $logger, MediaReferenceModel $mediaReference, string $language, bool $withSimilarMedia = false): MediaInfoModel
    {
        if ($mediaReference->source === 'st') {
            $queryBuilderSelect = self::generateStockMediaSql($mediaReference->id);
            $database           = new Database($logger, "st");
        } else {
            $queryBuilderSelect = self::generateSportMediaSql($mediaReference->id);
            $database           = new Database($logger, "sp");
        }
        $resultMedia = $database->queryPreparedStatement($queryBuilderSelect, 1)[0];
        $database->close();
        // set type
        if ($resultMedia->mediatype === '3') {
            $mediaReference->setType('V');
        } else {
            $mediaReference->setType('I');
        }
        // license
        list($mediaLicense, $mediaLicenseLanguagePointer, $mediaUsageLicenses) = self::getLicense($logger, $resultMedia->licencegroup);
        // restrictions
        try {
            $restrictionsDatabaseString = $resultMedia->restrictions;
            $mediaTypeTranslated        = $mediaReference->getMediaTypeInLanguage($mediaReference->getTypeAsIV(), $language);
            $mediaRestrictions          = TransformCountyRestrictionsModel::buildRestrictionsSentenceFromDatabaseString(
                $language,
                $restrictionsDatabaseString,
                $mediaTypeTranslated
            );
        } catch (UnexpectedValueException $unexpectedValueException) {
            $logger->warning($unexpectedValueException);
            $mediaRestrictions = null;
        }
        // caption
        if ($language === 'en') {
            $mediaTitle = $resultMedia->captionshorten;
        } else {
            $mediaTitle = $resultMedia->captionshortde;
        }
        if (strlen($mediaTitle) < 1) {
            $mediaTitle = trim(substr($resultMedia->prosa, 0, 100));
        }
        // similarmedia
        if ($withSimilarMedia === true) {
            $similarMedia = SimilarMediaModel::get($mediaReference, $language, $logger);
            $keywords     = KeywordFilterModel::getFromString($resultMedia->keywords, $logger, $language);
        } else {
            $similarMedia = null;
            $keywords     = "";
        }
        // build media info
        return new MediaInfoModel(
            $mediaReference,
            $mediaReference->getThumb($language),
            $mediaReference->getSrc($language),
            $resultMedia->fotografen,
            self::getIntIfItsNullReturnDefault($resultMedia->hoehe, 0),
            self::getIntIfItsNullReturnDefault($resultMedia->breite, 0),
            new CustomDateTimeModel($resultMedia->datum),
            self::getIntIfItsNullReturnDefault($resultMedia->cliplength, 0),
            $resultMedia->masterimage,
            $mediaTitle,
            $resultMedia->prosa,
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $keywords,
            self::getCategoryFromDatabase($logger, $resultMedia->category),
            $mediaRestrictions,
            $mediaUsageLicenses,
            $similarMedia,
            $mediaReference->getPrev($language)
        );
    }


}
