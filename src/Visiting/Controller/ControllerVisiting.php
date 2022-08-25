<?php

namespace App\Visiting\Controller;

use App\Unauthenticated\Controller\Actions\ActionError;
use App\Unauthenticated\Controller\ControllerUnauthenticated;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Controller\Actions\Action;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\HashUnknownException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;

/**
 * Controller to route requests to the responsible action.
 */
class ControllerVisiting extends ControllerUnauthenticated
{

    /**
     * Dictionary of post actions and their names
     *
     * @var array
     */
    private static array $postActionDict = [
        // comment
        Actions\Comment\ShowFolderCommentsAction::NAME => Actions\Comment\ShowFolderCommentsAction::class,
        Actions\Comment\ShowMediaCommentsAction::NAME => Actions\Comment\ShowMediaCommentsAction::class,
        // folder
        Actions\Folder\GetAllFolderInfosAction::NAME => Actions\Folder\GetAllFolderInfosAction::class,
        // media
        Actions\Media\GetAllMediaInFolderAction::NAME => Actions\Media\GetAllMediaInFolderAction::class,
        Actions\Media\GetPrevNextMediaInFolderAction::NAME => Actions\Media\GetPrevNextMediaInFolderAction::class,
        Actions\Media\GetMediaAdditionalInfosAction::NAME => Actions\Media\GetMediaAdditionalInfosAction::class,
    ];


    /**
     * Parses request, executes action and handles errors
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  http request that the api received
     * @param Response        $response empty http response
     *
     * @return     Response
     * @deprecated please use executeAction in the future, this function chooses the action on the json body and not it's path and http method.
     */
    public static function executeJSONAction(LoggerInterface $logger, Request $request, Response $response): Response
    {
        $logger->debug("Received POST Request");
        // parse request
        try {
            $parsedRequest = parent::parseRequestJSON($request);
        } catch (Exception $exception) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
        }
        $logger->debug($request->getBody());
        // get action
        try {
            $action = parent::getActionFromJSON($parsedRequest, self::$postActionDict);
        } catch (Exception $exception) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
        }

        // execute action
        try {
            $response = $action->action($logger, $parsedRequest, $response);
        } catch (MediaSourceUnknownException $mediaSourceUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::VALIDATION_ERROR, $mediaSourceUnknownException->getMessage()),
                400
            );
        } catch (MediaNotInFolderException $mediaNotInFolderException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $mediaNotInFolderException->getMessage()),
                $mediaNotInFolderException->getCode()
            );
        } catch (FolderUnknownException $folderUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $folderUnknownException->getMessage()),
                $folderUnknownException->getCode()
            );
        } catch (LicenseUnknownException $licenseUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $licenseUnknownException->getMessage()),
                $licenseUnknownException->getCode()
            );
        } catch (RequestInValidException $requestInValidException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::VALIDATION_ERROR, $requestInValidException->getMessage()),
                $requestInValidException->getCode()
            );
        } catch (UserMissingRightsException $userNotFolderOwnerException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::NOT_ALLOWED, $userNotFolderOwnerException->getMessage()),
                $userNotFolderOwnerException->getCode()
            );
        } catch (UserUnknownException $userUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $userUnknownException->getMessage()),
                $userUnknownException->getCode()
            );
        } catch (HashUnknownException $hashUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $hashUnknownException->getMessage()),
                $hashUnknownException->getCode()
            );
        } catch (Exception $exception) {
            // log exception
            $logger->error("Error when executing request. ".$exception->getMessage());
            $logger->info("Request: ".json_encode($parsedRequest->getParsedBody()));
            $logger->info("Response 500 - {$exception->getMessage()}");
            $logger->info($exception);
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::SERVER_ERROR, $exception->getMessage())
            );
        }

        $logger->debug($response->getBody());
        return $response;
        // response is 200
    }


}
