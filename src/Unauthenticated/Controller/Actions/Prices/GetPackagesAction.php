<?php

namespace App\Unauthenticated\Controller\Actions\Prices;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Model\Models\Prices\PackageModel;
use App\Unauthenticated\View\GetPackagesView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Action Class to get all license packages from the database.
 */
class GetPackagesAction extends Action
{

    const PATH = "unauthenticated/prices/packages";


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
        $packages     = PackageModel::getAll($logger);
        $responseData = GetPackagesView::view($packages);
        return parent::respondWithStatus($response, $responseData);
    }


}
