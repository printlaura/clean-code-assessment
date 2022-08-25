<?php

namespace App\Unauthenticated\Model\Models\Prices;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Model for a License.
 * The constants are being saved here too, make sure to change them when new options are added.
 */
class LicenseModel
{

    const DISTRIBUTIONS   = [
        "web",
        "unlimited",
        "webonly",
    ];
    const IMAGE_QUALITIES = [
        "high",
        "medium",
        "low",
    ];
    const MEDIATYPES      = [
        1,
        3,
    ];

    /**
     * Identification of this License
     *
     * @var integer
     */
    public int $id;

    public string $languagePointer;

    public string $nameShort;

    public string $nameLong;

    public int $credits;

    public string $timeStamp;

    public int $price;

    public string $currency;

    public string $imageQuality;

    public bool $isEditorialUsage;

    public bool $isCommercialUsage;

    public bool $isMultiUsage;

    public string $distribution;

    public int $mediaType;

    public int $groupID;
    
    public string $groupLanguagePointer;
    
    public string $groupShortName;

    public string $groupName;

    public int $groupMediaType;


    /**
     * Constructor for the LicenseModel
     *
     * @param int         $id
     * @param string      $languagePointer
     * @param string      $nameShort
     * @param string      $nameLong
     * @param string      $timeStamp
     * @param int         $credits
     * @param int         $price
     * @param string      $currency
     * @param string      $imageQuality         must be in IMAGE_QUALITIES
     * @param bool        $isEditorialUsage
     * @param bool        $isCommercialUsage
     * @param bool        $isMultiUsage
     * @param string      $distribution         must be in DISTRIBUTIONS
     * @param int         $mediaType            must be in MEDIA_TYPES
     * @param int|null    $groupID              licensegroup ID
     * @param string|null $groupLanguagePointer licensegroup languagepointer
     * @param string|null $groupShortName       licensegroup shortName
     * @param string|null $groupName            licensegroup name
     * @param int|null    $groupMediaType       licensegroup mediaType
     */
    public function __construct(
        int $id,
        string $languagePointer,
        string $nameShort,
        string $nameLong,
        string $timeStamp,
        int $credits,
        int $price,
        string $currency,
        string $imageQuality,
        bool $isEditorialUsage,
        bool $isCommercialUsage,
        bool $isMultiUsage,
        string $distribution,
        int $mediaType,
        int $groupID = null,
        string $groupLanguagePointer = null,
        string $groupShortName = null,
        string $groupName = null,
        int $groupMediaType = null
    ) {
        $this->id = $id;
        $this->languagePointer = $languagePointer;
        $this->nameShort       = $nameShort;
        $this->nameLong        = $nameLong;
        $this->timeStamp       = $timeStamp;
        $this->credits         = $credits;
        $this->price           = $price;
        $this->currency        = $currency;
        if (in_array($imageQuality, self::IMAGE_QUALITIES) === false) {
            throw new InvalidArgumentException("imageQuality must be one of ".implode(", ", self::IMAGE_QUALITIES));
        }
        $this->imageQuality      = $imageQuality;
        $this->isEditorialUsage  = $isEditorialUsage;
        $this->isCommercialUsage = $isCommercialUsage;
        $this->isMultiUsage      = $isMultiUsage;
        if (in_array($distribution, self::DISTRIBUTIONS) === false) {
            throw new InvalidArgumentException("distribution must be one of ".implode(", ", self::DISTRIBUTIONS)." not '$distribution'");
        }
        $this->distribution = $distribution;
        if (in_array($mediaType, self::MEDIATYPES) === false) {
            throw new InvalidArgumentException("mediaType must be one of ".implode(", ", self::MEDIATYPES));
        }
        $this->mediaType = $mediaType;
        $this->groupID   = $groupID;
        $this->groupLanguagePointer = $groupLanguagePointer;
        $this->groupShortName       = $groupShortName;
        $this->groupName            = $groupName;
        $this->groupMediaType       = $groupMediaType;
    }


    /**
     * Gets all licenses in the database, and returns them as LicenseModels.
     *
     * @param LoggerInterface $logger
     *
     * @return LicenseModel[]
     */
    public static function getAllLicensesWithLicensegroupInfo(LoggerInterface $logger): array
    {
        // get license table data
        $query = new QueryBuilderSelect();
        $query->select(
            "licence.id",
            "licence.languagepointer",
            "licence.shortname",
            "licence.name",
            "licence.timestamp",
            "licence.credits",
            "licence.price",
            "licence.currency",
            "licence.imagequality",
            "licence.editorialusage",
            "licence.comusage",
            "licence.distribution",
            "licence.multiusage",
            "licence.mediatype"
        );
        $query->from("licence");

        $database       = new Database($logger);
        $resultsLicense = $database->queryPreparedStatement($query);
        // turn results into LicenseModel
        $licenseModels = [];
        foreach ($resultsLicense as $result) {
            try {
                // get LicenseGroupID
                $licenseModel = self::getLicenseModel($logger, $result);

                $licenseModels[] = $licenseModel;
            } catch (Exception $exception) {
                $logger->error("Failed to get License from database. Skipping this license and continuing with next row.");
            }
        }
        return $licenseModels;
    }


    /**
     * Gets a single license from the database, and returns it as LicenseModel in an array.
     *
     * @param LoggerInterface $logger
     * @param int             $licenseID
     *
     * @return LicenseModel
     * @throws Exception license values from database not excepted
     */
    public static function getLicenseByLicenseID(LoggerInterface $logger, int $licenseID): ?LicenseModel
    {
        // get license table data
        $query = new QueryBuilderSelect();
        $query->select(
            "licence.id",
            "licence.languagepointer",
            "licence.shortname",
            "licence.name",
            "licence.credits",
            "licence.price",
            "licence.timestamp",
            "licence.currency",
            "licence.imagequality",
            "licence.editorialusage",
            "licence.comusage",
            "licence.distribution",
            "licence.multiusage",
            "licence.mediatype"
        );
        $query->from("licence");
        $query->andWhereEqualsInt("licence.id", $licenseID);
        $database      = new Database($logger);
        $resultLicense = $database->queryPreparedStatement($query, 1)[0];
        // turn results into LicenseModel
        try {
            // get LicenseGroupID
            return self::getLicenseModel($logger, $resultLicense);
        } catch (Exception $exception) {
            throw new Exception("Failed to get License from database. Skipping this license and continuing with next row.", $exception);
        }
    }


    /**
     * Turns the database value for a boolean into a php boolean.
     * Only for the licence table.
     *
     * @param string $dbValue must be true or false
     *
     * @return bool
     * @throws InvalidArgumentException when dbValue is not true or false
     */
    public static function getBool(string $dbValue): bool
    {
        if ($dbValue === "true") {
            return true;
        }
        if ($dbValue === "false") {
            return false;
        }
        throw new InvalidArgumentException("Unexpected value in $dbValue must be 'true' or 'false', found '$dbValue'.");
    }


    /**
     * Returns an int ID of LisenceGroup for licenseID
     *
     * @param LoggerInterface $logger
     * @param int             $licenseID
     *
     * @return int
     */
    private static function getLicenseGroupIDForLicense(LoggerInterface $logger, int $licenseID): int
    {
        $query = new QueryBuilderSelect();
        $query->select(
            "licencegroup"
        );
        // join license information to license id from licencegroup_licence
        $query->from("licencegroup_licence");
        $query->andWhereEqualsInt("licencegroup_licence.licence", $licenseID);
        // query the db
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($query);

        return (int) $results[0]->licencegroup;
    }


    /**
     * Turns sql result into LicenseModel
     *
     * @param LoggerInterface $logger
     * @param $result
     *
     * @return LicenseModel
     */
    public static function getLicenseModel(LoggerInterface $logger, $result): LicenseModel
    {
        $licenseGroupID = self::getLicenseGroupIDForLicense($logger, (int) $result->id);
        // getLicenseGroupModel with LicenseGroupID
        $licenseGroup = LicenseGroupModel::getLicensegroupByID($logger, $licenseGroupID);

        return new LicenseModel(
            (int) $result->id,
            $result->languagepointer,
            $result->shortname,
            $result->name,
            $result->timestamp,
            (int) $result->credits,
            (int) $result->price,
            $result->currency,
            $result->imagequality,
            self::getBool($result->editorialusage),
            self::getBool($result->comusage),
            self::getBool($result->multiusage),
            $result->distribution,
            $result->mediatype,
            $licenseGroup->id,
            $licenseGroup->languagePointer,
            $licenseGroup->nameShort,
            $licenseGroup->nameLong,
            $licenseGroup->mediaType
        );
    }


}
