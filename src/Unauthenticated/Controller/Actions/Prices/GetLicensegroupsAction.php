<?php

namespace App\Unauthenticated\Controller\Actions\Prices;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Model\Models\Prices\LicensegroupModel;
use App\Unauthenticated\View\GetLicensegroupsView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Action Class for getting all Licensegroups from the database.
 */
class GetLicensegroupsAction extends Action
{
    const PATH = "unauthenticated/prices/licensegroups";


    /**
     * Entry point of the class.
     *
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param Response        $response
     *
     * @return Response response with data
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        $licensegroupModels = LicensegroupModel::getLicenseGroupsWithLicenses($logger);
        $data = GetLicensegroupsView::view($licensegroupModels);
        // respond
        return parent::respondWithStatus(
            $response,
            $data
        );
    }


}
