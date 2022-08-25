<?php

namespace App\Unauthenticated\Model\Models\Prices;

use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Model Class for a license group. Does not need to contain a @see LicenseModel.
 */
class LicensegroupModel extends Model
{

    public int $id;

    public string $languagePointer;

    public string $nameShort;

    public string $nameLong;

    public int $mediaType;

    /**
     * Optional array of licenses belonging to the group
     *
     * @var LicenseModel[]
     */
    public array $licenses = [];


    /**
     * Constructor for the LicensegroupModel
     *
     * @param int    $id              id of the licensegroup
     * @param string $languagePointer
     * @param string $nameShort
     * @param string $nameLong
     * @param int    $mediaType
     */
    public function __construct(
        int $id,
        string $languagePointer,
        string $nameShort,
        string $nameLong,
        int $mediaType
    ) {
        $this->id = $id;
        $this->languagePointer = $languagePointer;
        $this->nameShort       = $nameShort;
        $this->nameLong        = $nameLong;
        $this->mediaType       = $mediaType;
    }


    /**
     * Gets all lichence groups from the database with licenses for each group
     *
     * @param LoggerInterface $logger
     *
     * @return LicensegroupModel[]
     */
    public static function getLicenseGroupsWithLicenses(LoggerInterface $logger): array
    {
        // prepare query to get all license groups from db.licencegroup
        $query = new QueryBuilderSelect();
        $query->select(
            "licencegroup.id",
            "licencegroup.languagepointer",
            "licencegroup.shortname ",
            "licencegroup.name",
            "licencegroup.mediatype"
        );
        $query->from("licencegroup");
        $query->andWhereEqualsInt("licencegroup.active", 1);
        
        // query the db
        $database = new Database($logger);
        $results  = $database->queryPreparedStatement($query);

        // get individual licenses and attach them to each model of a license group
        $licensegroupModels = [];
        foreach ($results as $result) {
            // check if id needs converting to int
            $licensegroupModel = new LicensegroupModel(
                (int) $result->id,
                $result->languagepointer,
                $result->shortname,
                $result->name,
                $result->mediatype
            );
            try {
                $licenses = self::getLicensesForLicenseGroupById($logger, $licensegroupModel->id);
                $licensegroupModel->licenses = $licenses;
                $licensegroupModels[]        = $licensegroupModel;
            } catch (Exception $exception) {
                $logger->error($exception);
            }
        }
        
        return $licensegroupModels;
    }


    /**
     * Gets a single lichencegroup from the database by a licensegroupID
     *
     * @param LoggerInterface $logger
     * @param int             $licensegroupID
     *
     * @return LicensegroupModel
     */
    public static function getLicensegroupByID(LoggerInterface $logger, int $licensegroupID): LicensegroupModel
    {
        // prepare query to get all license groups from db.licencegroup
        $query = new QueryBuilderSelect();
        $query->select(
            "licencegroup.id",
            "licencegroup.languagepointer",
            "licencegroup.shortname ",
            "licencegroup.name",
            "licencegroup.mediatype"
        );
        $query->from("licencegroup");
        $query->andWhereEqualsInt("licencegroup.active", 1);
        $query->andWhereEqualsInt("licencegroup.id", $licensegroupID);

        // query the db for one entry
        $database = new Database($logger);
        $result   = $database->queryPreparedStatement($query, 1)[0];

        // create a LicenseGroupModel
        return new LicensegroupModel(
            (int) $result->id,
            $result->languagepointer,
            $result->shortname,
            $result->name,
            $result->mediatype
        );
    }


    /**
     * Returns an array of LisenceModel for a LicensegroupID
     *
     * @param LoggerInterface $logger
     * @param int             $licensegroupID
     *
     * @return LicenseModel[]
     * @throws Exception license values from database not excepted
     */
    public static function getLicensesForLicenseGroupById(LoggerInterface $logger, int $licensegroupID): array
    {
        // get license IDs
        $licenseIDs = self::getLicenseIDsForGroup($logger, $licensegroupID);

        $licenses = [];
        // TODO: reformat the following request when the implementation of OR conditions in the query builder is finished
        // get license info for each ID (ODBC does not allow a join here, reason unknown)
        // get licenses from db
        foreach ($licenseIDs as $licenseID) {
            try {
                $licenseModel = LicenseModel::getLicenseByLicenseID($logger, $licenseID);
                $licenses[]   = $licenseModel;
            } catch (Exception $exception) {
                $logger->error($exception);
            }
        }
        return $licenses;
    }


    /**
     * Returns an array of LisenceIDs as object from db for a LicensegroupID
     *
     * @param LoggerInterface $logger
     * @param int             $licensegroupID
     *
     * @return array[int]     $licenseIDs
     */
    private static function getLicenseIDsForGroup(LoggerInterface $logger, int $licensegroupID): array
    {
        $query = new QueryBuilderSelect();
        $query->select(
            "licence"
        );
        // join license information to license id from licencegroup_licence
        $query->from("licencegroup_licence");
        $query->andWhereEqualsInt("licencegroup_licence.licencegroup", $licensegroupID);
        // query the db
        $database   = new Database($logger);
        $results    = $database->queryPreparedStatement($query);
        $licenseIDs = [];
        // loop and return array
        foreach ($results as $result) {
            $licenseIDs[] = (int) $result->licence;
        }
        return $licenseIDs;
    }


}
