<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Controller\Settings\RelativePaths;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\Model;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Model for accessing the pond5 source api. For getting metadata and preview url.
 * Pond5 is a media source provider.
 */
class Pond5Model extends Model
{
    const API_KEY_FULL_RF    = 'b3L3ZOHU137';
    const API_SECRET_FULL_RF = 'sV6P6FbFSH1J137';

    // const API_KEY_WEB    = '6XCah6Q3138';
    // const API_SECRET_WEB = '7etMYIlXUFoD138';

    const DETAILS_URL       = 'https://api-reseller.pond5.com/api/v2/items/';
    const IMAGE_PREVIEW_URL = "https://p5iconsp.s3-accelerate.amazonaws.com/";
    const VIDEO_PREVIEW_URL = "https://p5resellerp.s3-accelerate.amazonaws.com/";


    /**
     * Returns MediaInfo from pond5 api metadata
     *
     * @param LoggerInterface     $logger
     * @param MediaReferenceModel $mediaReference
     * @param string              $language
     * @param bool                $withSimilarMedia true to get MediaInfoModel with similar media
     *
     * @return MediaInfoModel
     * @throws LicenseUnknownException
     * @throws Exception
     */
    public static function getMediaInfo(LoggerInterface $logger, MediaReferenceModel $mediaReference, string $language, bool $withSimilarMedia = false): MediaInfoModel
    {
        $pondMetaResponse = Pond5Model::getMetadata($logger, $mediaReference->id);
        if (count($pondMetaResponse) < 1) {
            throw new MediaUnknownException($mediaReference);
        }
        $mediaReference->setType($pondMetaResponse["type"]);
        // getLicense
        if ($pondMetaResponse["editorial"] === true) {
            $licenseGroupId = 7;
        } else {
            $licenseGroupId = 15;
        }
        list(
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $mediaUsageLicenses) = MediaInfoModel::getLicense($logger, $licenseGroupId);
        // similar media
        if ($withSimilarMedia === true) {
            $similarMedia = SimilarMediaModel::get($mediaReference, $language, $logger);
            $keywords     = KeywordFilterModel::get($pondMetaResponse["keywords"], $logger, $language);
        } else {
            $similarMedia = null;
            $keywords     = "";
        }
        // build mediaInfoModel
        return new MediaInfoModel(
            $mediaReference,
            Pond5Model::getImagePreviewUrl($mediaReference->id),
            Pond5Model::getVideoPreviewUrl($mediaReference->id),
            $pondMetaResponse["authorName"],
            $pondMetaResponse["height"],
            $pondMetaResponse["width"],
            new CustomDateTimeModel($pondMetaResponse["createdDate"]),
            MediaInfoModel::convertClipLength($pondMetaResponse["versions"][0]["duration"]),
            null,
            $pondMetaResponse["title"],
            $pondMetaResponse["description"],
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $keywords,
            [],
            MediaInfoModel::getTeritoryRestrictionsFromDatabase($logger, $mediaReference, $language),
            $mediaUsageLicenses,
            $similarMedia,
            Pond5Model::getVideoPreviewUrl($mediaReference->id)
        );
    }


    /**
     * Retrieves MediaModel data from pond5 api
     *
     * @param LoggerInterface $logger
     * @param int             $mediaId
     *
     * @return MediaModel
     * @throws LicenseUnknownException
     * @throws MediaUnknownException
     * @throws Exception on curl error
     */
    public static function getMediaModel(LoggerInterface $logger, int $mediaId): MediaModel
    {
        $mediaReference   = new MediaReferenceModel("pond5", $mediaId);
        $pondMetaResponse = self::getMetadata($logger, $mediaId);
        if (count($pondMetaResponse) < 1) {
            throw new MediaUnknownException($mediaReference);
        }
        $mediaReference->setType($pondMetaResponse["type"]);
        // getLicense
        if ($pondMetaResponse["editorial"] === true) {
            $licenseGroupId = 7;
        } else {
            $licenseGroupId = 15;
        }
        list(
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $mediaUsageLicenses) = MediaInfoModel::getLicense($logger, $licenseGroupId);

        $mediaClipLength   = MediaInfoModel::convertClipLength($pondMetaResponse["versions"][0]["duration"]);
        $mediaCreationDate = new CustomDateTimeModel($pondMetaResponse["createdDate"]);

        return new MediaModel(
            $mediaReference,
            Pond5Model::getImagePreviewUrl($mediaReference->id),
            Pond5Model::getVideoPreviewUrl($mediaReference->id),
            null,
            $pondMetaResponse["title"],
            $pondMetaResponse["description"],
            null,
            null,
            (int) $pondMetaResponse["height"],
            (int) $pondMetaResponse["width"],
            $mediaLicense,
            $mediaLicenseLanguagePointer,
            $mediaUsageLicenses,
            $pondMetaResponse["authorName"],
            (empty($mediaClipLength) === true ? "" : $mediaClipLength),
            $mediaCreationDate,
            null,
            Pond5Model::getVideoPreviewUrl($mediaReference->id)
        );
    }


    /**
     * Retrieves media metadata from pond5 api
     *
     * @param LoggerInterface $logger
     * @param int             $mediaId
     *
     * @return mixed php object decoded from json string
     * @throws Exception when curl failed
     */
    private static function getMetadata(LoggerInterface $logger, int $mediaId)
    {
        $header = [
            "Content-Type: application/json",
            "key: ".self::API_KEY_FULL_RF,
            "secret: ".self::API_SECRET_FULL_RF,
        ];

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => self::DETAILS_URL.$mediaId,
                CURLOPT_FAILONERROR => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_COOKIEJAR => RelativePaths::getAbsolutePathTo("/var/cache/curlCookiePond5.txt")
            ]
        );

        $response = curl_exec($curl);
        if (curl_errno($curl) !== 0) {
            $logger->error("Curl failed with error message: ".curl_error($curl));
            throw new Exception("Curl failed with error message: ".curl_error($curl));
        }
        curl_close($curl);
        return json_decode($response, true);
        // TODO: change the return type it is not clear what is being returned here, maybe return MediaModel?
    }


    /**
     * Build video preview url using media ID
     *
     * @param int $mediaId
     *
     * @return string
     */
    public static function getVideoPreviewUrl(int $mediaId): string
    {
        $mediaId = sprintf('%09d', $mediaId);
        return self::VIDEO_PREVIEW_URL.$mediaId.".mp4";
    }


    /**
     * Build image preview url using media ID
     *
     * @param int $mediaId
     *
     * @return string
     */
    public static function getImagePreviewUrl(int $mediaId): string
    {
        $mediaId = sprintf('%09d', $mediaId);
        return self::IMAGE_PREVIEW_URL.$mediaId."_iconl.jpeg";
    }


    /**
     * Returns an array of mediaInfoModels for multiple items in $mediRreferences Array
     *
     * @param LoggerInterface $logger
     * @param array           $mediaReferences array of refferences to fetch media info by
     * @param string          $language
     *
     * @return array              array of MediaInfoModels
     * @throws LicenseUnknownException
     */
    public static function getSimilarMediaInfos(LoggerInterface $logger, array $mediaReferences, string $language): array
    {
        $mediaInfos = [];
        foreach ($mediaReferences as $mediaReference) {
            $mediaInfos[] = self::getMediaInfo($logger, $mediaReference, $language);
        }
        return $mediaInfos;
    }


    // TODO this might be slow for a large nr of media. Might need refactoring, keeping curl calls in mind.
}
