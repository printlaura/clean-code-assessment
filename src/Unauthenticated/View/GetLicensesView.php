<?php

namespace App\Unauthenticated\View;

use App\Unauthenticated\Model\Models\Prices\LicenseModel;

/**
 * View Class for turning @see LicenseModel into the required response.
 * Using a view makes easier to change the response. The response that gets created here does not match the guidelines,
 * please edit at a later time when possible.
 */
class GetLicensesView
{


    /**
     * Entrypoint for the view class.
     *
     * @param LicenseModel[] $licenseModels
     *
     * @return array data that will be converted to json by the action.
     */
    public static function view(array $licenseModels): array
    {
        $view = [];
        foreach ($licenseModels as $licenseModel) {
            $licenseView = [
                "l_id" => (string) $licenseModel->id,
                "l_lang" => $licenseModel->languagePointer,
                "l_shortname" => $licenseModel->nameShort,
                "l_name" => $licenseModel->nameLong,
                "l_cresdits" => (string) $licenseModel->credits,
                "l_price" => (string) $licenseModel->price,
                "l_currency" => $licenseModel->currency,
                "l_imagequality" => $licenseModel->imageQuality,
                "l_editorialusage" => self::getBoolString($licenseModel->isEditorialUsage),
                "l_comusage" => self::getBoolString($licenseModel->isCommercialUsage),
                "l_distribution" => $licenseModel->distribution,
                "l_multiusage" => self::getBoolString($licenseModel->isMultiUsage),
                "lg_id" => $licenseModel->groupID,
                "lg_lang" => $licenseModel->groupLanguagePointer,
                "lg_shorname" => $licenseModel->groupShortName,
                "lg_name" => $licenseModel->groupName,
                "lg_mediatype" => $licenseModel->groupMediaType
            ];
            $view[]      = $licenseView;
        }
        
        return $view;
    }


    /**
     * Turns bool value into a string.
     * This is needed, because bools are saved as strings in the license database table.
     *
     * @param bool $value
     *
     * @return string true or false
     */
    public static function getBoolString(bool $value): string
    {
        if ($value === true) {
            return "true";
        } else {
            return "false";
        }
    }


}
