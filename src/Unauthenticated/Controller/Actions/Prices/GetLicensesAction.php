<?php

namespace App\Unauthenticated\Controller\Actions\Prices;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Model\Models\Prices\LicenseModel;
use App\Unauthenticated\View\GetLicensesView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Action Class to get all licenses from the database.
 */
class GetLicensesAction extends Action
{
    const PATH = "unauthenticated/prices/licenses";


    /**
     * Entry point into the action class.
     *
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param Response        $response
     *
     * @return Response response with data
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        $licenseModels = LicenseModel::getAllLicensesWithLicensegroupInfo($logger);
        $responseData  = GetLicensesView::view($licenseModels);
        return parent::respondWithStatus($response, $responseData);
    }


}
