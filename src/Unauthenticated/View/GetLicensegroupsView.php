<?php

namespace App\Unauthenticated\View;

use App\Unauthenticated\Model\Models\Prices\LicensegroupModel;

/**
 * View Class for turning @see LicensegroupModel into the required response.
 * Using a view makes easier to change the response.
 */
class GetLicensegroupsView
{


    /**
     * Entrypoint for the view class.
     *
     * @param LicensegroupModel[] $licensegroupModels
     *
     * @return array data that will be converted to json by the action.
     */
    public static function view(array $licensegroupModels): array
    {
        $view = [];
        foreach ($licensegroupModels as $licensegroupModel) {
            $licensesSubView = [];
            foreach ($licensegroupModel->licenses as $licenseModel) {
                $licenseView       = [
                    "l_id" => (string) $licenseModel->id,
                    "l_lang" => $licenseModel->languagePointer,
                    "l_shortname" => $licenseModel->nameShort,
                    "l_name" => $licenseModel->nameLong,
                    "l_cresdits" => (string) $licenseModel->credits,
                    "l_price" => (string) $licenseModel->price,
                    "l_currency" => $licenseModel->currency,
                    "l_imagequality" => $licenseModel->imageQuality,
                    "l_editorialusage" => GetLicensesView::getBoolString($licenseModel->isEditorialUsage),
                    "l_comusage" => GetLicensesView::getBoolString($licenseModel->isCommercialUsage),
                    "l_distribution" => $licenseModel->distribution,
                    "l_multiusage" => GetLicensesView::getBoolString($licenseModel->isMultiUsage),
                    "lg_id" => $licenseModel->groupID,
                    "lg_lang" => $licenseModel->groupLanguagePointer,
                    "lg_shorname" => $licenseModel->groupShortName,
                    "lg_name" => $licenseModel->groupName,
                    "lg_mediatype" => $licenseModel->groupMediaType
                ];
                $licensesSubView[] = $licenseView;
            }
        
            $subView = [
                "langpointer" => $licensegroupModel->languagePointer,
                "shortname" => $licensegroupModel->nameShort,
                "name" => $licensegroupModel->nameLong,
                "mediatype" => (string) $licensegroupModel->mediaType,
                "licence" => $licensesSubView
            ];

            $view[(string) $licensegroupModel->id] = $subView;
        }
        return $view;
    }


}
