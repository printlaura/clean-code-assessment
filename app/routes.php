<?php

declare(strict_types=1);

namespace App;

use App\Authenticated\Controller\ControllerAuthenticated;
use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionError;
use App\Unauthenticated\Controller\Actions\UnknownAction;
use App\Unauthenticated\Controller\ControllerUnauthenticated;
use App\Visiting\Controller\ControllerVisiting;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;

return function (App $app) {
    $logger = $app->getContainer()->get(LoggerInterface::class);

    $app->options(
        '/{routes:.+}',
        function (Request $request, Response $response) use ($logger) {
            $logger->debug("Received options request, will return 200");
            $response->withStatus(200, "Options always ok");
            return $response;
        }
    );

    $app->get(
        '/api/unauthenticated/prices/packages',
        function (Request $request, Response $response) use ($logger) {
            $logger->debug($request->getUri());
            return ControllerUnauthenticated::executeAction($logger, $request, $response);
        }
    );
    $app->get(
        '/api/unauthenticated/prices/licenses',
        function (Request $request, Response $response) use ($logger) {
            return ControllerUnauthenticated::executeAction($logger, $request, $response);
        }
    );
    $app->get(
        '/api/unauthenticated/prices/licensegroups',
        function (Request $request, Response $response) use ($logger) {
            return ControllerUnauthenticated::executeAction($logger, $request, $response);
        }
    );
    $app->post(
        '/api',
        function (Request $request, Response $response) use ($logger) {
            $logger->debug($request->getUri());
            return ControllerUnauthenticated::executeJSONAction($logger, $request, $response);
        }
    );
    $app->post(
        '/api/lightboxopen',
        function (Request $request, Response $response) use ($logger) {
            $logger->debug($request->getUri());
            return ControllerVisiting::executeJSONAction($logger, $request, $response);
        }
    );
    $app->post(
        '/api/lightbox',
        function (Request $request, Response $response) use ($logger) {
            return ControllerAuthenticated::executeJSONAction($logger, $request, $response);
        }
    );
    $app->post(
        '/api/*',
        function (Request $request, Response $response) use ($logger) {
            return UnknownAction::action($logger, $request, $response);
        }
    );

    // Catch-all route to serve a 404 Not Found page if none of the routes match
    // NOTE: make sure this route is defined last
    $app->map(
        [
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'PATCH',
        ],
        '/{routes:.+}',
        function (Request $request, Response $response) use ($logger) {
            $method = $request->getMethod();
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::NOT_ALLOWED, "Endpoint accessed with $method unknown."),
                404
            );
        }
    );
};
