<?php

namespace App\Unauthenticated\Model\Models\Prices;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\CustomDateTimeModel;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Model for License Packages.
 * A package is now used only for selling credits.
 */
class PackageModel extends Model
{

    public int $id;

    public int $groupId;

    public int $licenseId;

    public string $languagepointer;

    public string $nameShort;

    public string $nameLong;

    public int $price;

    public string $currency;

    public int $credits;

    public int $period;

    public int $periodType;

    public string $isRefresh;

    public CustomDateTimeModel $creationDate;

    public int $sort;

    public ?string $imageQuality;

    public ?string $usage;

    public ?string $distribution;

    public ?string $isMultipleUsage;

    public ?int $mediaType;

    public int $license;

    public ?string $lLanguagePointer = null;

    public ?int $lMediaType = null;

    public ?string $lNameShort = null;

    public ?string $lNameLong = null;

    public ?int $lCredits = null;

    public ?CustomDateTimeModel $lTimeStamp = null;


    /**
     * Constructor for the PackageModel
     *
     * @param int                 $id              package identification number
     * @param int                 $groupId         @see LicensegroupModel
     * @param int                 $licenseId       @see LicenseModel
     * @param string              $languagepointer
     * @param string              $nameShort
     * @param string              $nameLong
     * @param int                 $price
     * @param string              $currency
     * @param int                 $credits
     * @param int                 $period
     * @param int                 $periodType
     * @param string              $isRefresh
     * @param CustomDateTimeModel $creationDate
     * @param int                 $sort            defines display order of packages
     * @param LicenseModel|null   $licenseModel    optional
     * @param string|null         $imageQuality
     * @param string|null         $usage
     * @param string|null         $distribution    must be one of @see LicenseModel::DISTRIBUTIONS
     * @param string|null         $isMultipleUsage
     * @param int|null            $mediaType       must be one of @see LicenseModel::MEDIA_TYPES
     */
    public function __construct(
        int            $id,
        int            $groupId,
        int            $licenseId,
        string         $languagepointer,
        string         $nameShort,
        string         $nameLong,
        int            $price,
        string         $currency,
        int            $credits,
        int            $period,
        int            $periodType,
        string           $isRefresh,
        CustomDateTimeModel $creationDate,
        int $sort,
        ?LicenseModel $licenseModel,
        string $imageQuality = null,
        string $usage = null,
        string $distribution = null,
        string $isMultipleUsage = null,
        int $mediaType = null
    ) {
        $this->id        = $id;
        $this->groupId   = $groupId;
        $this->licenseId = $licenseId;
        $this->languagepointer = $languagepointer;
        $this->nameShort       = $nameShort;
        $this->nameLong        = $nameLong;
        $this->price           = $price;
        $this->currency        = $currency;
        $this->credits         = $credits;
        $this->period          = $period;
        $this->periodType      = $periodType;
        $this->isRefresh       = $isRefresh;
        $this->creationDate    = $creationDate;
        $this->sort            = $sort;
        // if (in_array($imageQuality, LicenseModel::IMAGE_QUALITIES) === false) {
        // throw new InvalidArgumentException("imageQuality must be one of ".implode(", ", LicenseModel::IMAGE_QUALITIES));
        // }
        $this->imageQuality = $imageQuality;
        $this->usage        = $usage;
        if (in_array($distribution, LicenseModel::DISTRIBUTIONS) === false) {
            throw new InvalidArgumentException("distribution must be one of ".implode(", ", LicenseModel::DISTRIBUTIONS));
        }
        $this->distribution    = $distribution;
        $this->isMultipleUsage = $isMultipleUsage;
        if (in_array($mediaType, LicenseModel::MEDIATYPES) === false) {
            throw new InvalidArgumentException("mediaType must be one of ".implode(", ", LicenseModel::MEDIATYPES));
        }
        $this->mediaType = $mediaType;
        if ($licenseModel !== null) {
            $this->lLanguagePointer = $licenseModel->languagePointer;
            $this->lMediaType       = $licenseModel->mediaType;
            $this->lNameShort       = $licenseModel->nameShort;
            $this->lNameLong        = $licenseModel->nameLong;
            $this->lCredits         = $licenseModel->credits;
            $this->lTimeStamp       = new CustomDateTimeModel($licenseModel->timeStamp);
        }
    }


    /**
     * Gets all packages from the database, including their licenses.
     * Currently, the db has licences for just package group 1 only, therefore the result only includes
     * that group. To include group 2 review condition: {$query->andWhereEqualsFunc("buypackages.licence", "licence.id");}
     *
     * @param LoggerInterface $logger
     *
     * @return PackageModel[]
     */
    public static function getAll(LoggerInterface $logger): array
    {
        $query = new QueryBuilderSelect();

        $query->select(
            "buypackages.id",
            "buypackages.buypackagesgroup",
            "buypackages.licence",
            "buypackages.languagepointer",
            "buypackages.shortname",
            "buypackages.name",
            "buypackages.price",
            "buypackages.currency",
            "buypackages.credits",
            "buypackages.period",
            "buypackages.periodtype",
            "buypackages.refresh",
            "buypackages.timestamp",
            "buypackages.sort",
            "buypackages.imagequality",
            "buypackages.usage",
            "buypackages.distribution",
            "buypackages.multiuse",
            "buypackages.mediatype",
        );
        $query->from("buypackages");
        $query->andWhereEqualsInt("buypackages.active", 1);
        $query->andWhereIsNotNull("buypackages.imagequality");
        $query->sort("buypackages.buypackagesgroup", "buypackages.sort");

        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($query);
        $packages = [];
        foreach ($results as $result) {
            try {
                // get LicenseModel using buyPackages->licence
                try {
                    $license = LicenseModel::getLicenseByLicenseID($logger, $result->licence);
                } catch (Exception $exception) {
                    $license = null;
                }
                $package    = new PackageModel(
                    $result->id,
                    $result->buypackagesgroup,
                    $result->licence,
                    $result->languagepointer,
                    $result->shortname,
                    $result->name,
                    (int) $result->price,
                    $result->currency,
                    $result->credits,
                    $result->period,
                    (int) $result->periodtype,
                    $result->refresh,
                    new CustomDateTimeModel($result->timestamp),
                    $result->sort,
                    $license,
                    $result->imagequality,
                    $result->usage,
                    $result->distribution,
                    $result->multiuse,
                    $result->mediatype
                );
                $packages[] = $package;
            } catch (Exception $exception) {
                $logger->error("Failed to get License from database".$exception);
            }
        }
        return $packages;
    }


}
