<?php

namespace App\Unauthenticated\View;

use App\Unauthenticated\Model\Models\Prices\PackageModel;

/**
 * View Class for turning @see PackageModel into the required response.
 * Using a view makes easier to change the response. The response that gets created here does not match the guidelines,
 * please edit at a later time when possible.
 */
class GetPackagesView
{


    /**
     * Entrypoint for the view class.
     *
     * @param PackageModel[] $packageModels
     *
     * @return array data that will be converted to json by the action.
     */
    public static function view(array $packageModels): array
    {
        $view = [];
        foreach ($packageModels as $packageModel) {
            $subView = [
                "bp_id" => (string) $packageModel->id,
                "bpg_id" => (string) $packageModel->groupId,
                "licence" => (string) $packageModel->licenseId,
                "bp_lang" => $packageModel->languagepointer,
                "bp_short" => $packageModel->nameShort,
                "bp_name" => $packageModel->nameLong,
                "price" => (string) $packageModel->price,
                "currency" => $packageModel->currency,
                "credits" => (string) $packageModel->credits,
                "period" => $packageModel->period,
                "periodtype" => $packageModel->periodType,
                "refresh" => $packageModel->isRefresh,
                "bp_timestamp" => $packageModel->creationDate,
                "sort" => $packageModel->sort,
                "bp_imagequality" => $packageModel->imageQuality,
                "bp_usage" => $packageModel->usage,
                "bp_distribution" => $packageModel->distribution,
                "bp_multiuse" => $packageModel->isMultipleUsage,
                "bp_mediatype" => $packageModel->mediaType,
                "l_lang" => $packageModel->lLanguagePointer,
                "l_mediatype" => $packageModel->lMediaType,
                "l_shortname" => $packageModel->lNameShort,
                "l_name" => $packageModel->lNameLong,
                "l_credits" => $packageModel->lCredits,
                "l_timestamp" => $packageModel->lTimeStamp
            ];
            $view[]  = $subView;
        }

        // To group array of objects using key "bpg_id"
        $packageView = [];
        foreach ($view as $subView) {
            $packageView[$subView["bpg_id"]][] = $subView;
        }
        return $packageView;
    }


}
