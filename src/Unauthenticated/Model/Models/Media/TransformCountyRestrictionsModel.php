<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Language\LanguageFileModel;
use UnexpectedValueException;

/**
 * Class for converting the restriction from the DB in a readble format
 */
class TransformCountyRestrictionsModel extends Model
{


    /**
     * Convert the string from the DB into a readble format
     *
     * @param string      $language
     * @param string|null $restrictionsDatabaseString the input string from the db
     * @param string      $mediaType                  type of the media
     *
     * @return string|null
     */
    public static function buildRestrictionsSentenceFromDatabaseString(string $language, ?string $restrictionsDatabaseString, string $mediaType): ?string
    {
        /*
         * PUBLICATIONxINxGERxAUSxONLY
         * PUBLICATIONxNOTxINxGERxAUS
         *
         * The string can either be a whitelist or blacklist.
         * Country keys are separated with an 'x'
         *
         * This needs to paste into the sentence from the langage file:
         * "This ###mediatype### can ###only### be published in ###country###."
         *
         * Media the customer can't use because of the customers country restrictions won't show up. This is handled is the search.
         */

        if ($restrictionsDatabaseString === null) {
            return null;
        }
        if (empty($restrictionsDatabaseString) === true) {
            return null;
        }

        $languageFile    = new LanguageFileModel($language);
        $languageContent = $languageFile->read();
        // get only string
        $only = self::getOnlyString($languageContent, $restrictionsDatabaseString);
        // get contries string
        $countriesString = self::getCountryString($languageContent, $restrictionsDatabaseString);
        // build sentence
        $sentenceRaw = $languageContent["restriction"]["sentence"];

        $sentenceWithMediaType        = str_replace("###mediatype###", $mediaType, $sentenceRaw);
        $sentenceWithMediaTypeAndOnly = str_replace("###only###", $only, $sentenceWithMediaType);
        return str_replace("###country###", $countriesString, $sentenceWithMediaTypeAndOnly);
    }


    /**
     * Convert the countries in a string into a readble format
     *
     * @param array  $languageContent            content of the language file
     * @param string $restrictionsDatabaseString
     *
     * @throws UnexpectedValueException
     * @return string full name of contries connected with commas and "and"
     */
    private static function getCountryString(array $languageContent, string $restrictionsDatabaseString): string
    {
        $isWhiteList = str_starts_with($restrictionsDatabaseString, "PUBLICATIONxINx") === true;

        if ($isWhiteList === true) {
            $countriesRaw = str_replace("PUBLICATIONxINx", "", $restrictionsDatabaseString);
            $countriesRaw = str_replace("xONLY", "", $countriesRaw);
        } else {
            $countriesRaw = str_replace("PUBLICATIONxNOTxINx", "", $restrictionsDatabaseString);
        }
        $countryKeys = explode("x", $countriesRaw);
        if (count($countryKeys) < 1) {
            throw new UnexpectedValueException(
                "Expected restrictionsDatabaseString to contain country keys, but found '$countriesRaw'"
            );
        }

        $lastKey          = array_key_last($countryKeys);
        $combineCountries = "";
        foreach ($countryKeys as $key => $countryKey) {
            $combineCountries .= $languageContent["restriction"]["country"][$countryKey];
            if ($key < ($lastKey - 1)) {
                $combineCountries .= ", ";
            } else if ($key === ($lastKey - 1)) {
                $combineCountries .= " ".$languageContent["restriction"]["and"]." ";
            }
        }
        return $combineCountries;
    }


    /**
     * Get string from language file for whilelist (only) or blacklist (not)
     *
     * @param array  $languageContent
     * @param string $restrictionsDatabaseString
     *
     * @throws UnexpectedValueException
     * @return string
     */
    private static function getOnlyString(array $languageContent, string $restrictionsDatabaseString): string
    {
        // only or not
        if (str_starts_with($restrictionsDatabaseString, "PUBLICATIONxINx") === true) {
            // is whitelisting
            $only = $languageContent["restriction"]["only"];
        } else if (str_starts_with($restrictionsDatabaseString, "PUBLICATIONxNOTxINx") === true) {
            // is blacklisting
            $only = $languageContent["restriction"]["not"];
        } else {
            throw new UnexpectedValueException(
                "Expected restrictionsDatabaseString to start with 'PUBLICATIONxINx' or 'PUBLICATIONxNOTxINx', but found '$restrictionsDatabaseString'"
            );
        }
        return $only;
    }


}
