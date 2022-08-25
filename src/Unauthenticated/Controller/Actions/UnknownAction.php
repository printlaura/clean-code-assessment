<?php

namespace App\Unauthenticated\Controller\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Action that will return 400 error
 */
class UnknownAction extends Action
{


    /**
     * Call this action, when the action name or endpoint is unknown.
     *
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param Response        $response
     *
     * @return Response error response
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        return parent::respondWithError(
            $logger,
            $response,
            new ActionError(ActionError::BAD_REQUEST, "This action does not exist."),
            400
        );
    }


}
