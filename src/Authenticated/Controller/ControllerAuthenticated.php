<?php

namespace App\Authenticated\Controller;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionError;
use App\Unauthenticated\Controller\ControllerUnauthenticated;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;

/**
 * Controller to route requests to the responsible action.
 */
class ControllerAuthenticated extends ControllerUnauthenticated
{

    /**
     * Dictionary of post actions and their names
     *
     * @var array
     */
    const POST_ACTION_DICTIONARY = [
        // activities
        Actions\Activities\MarkActivitiesAction::NAME => Actions\Activities\MarkActivitiesAction::class,
        Actions\Activities\ShowActivitiesAction::NAME => Actions\Activities\ShowActivitiesAction::class,
        // comment
        Actions\Comment\AddCommentAction::NAME => Actions\Comment\AddCommentAction::class,
        Actions\Comment\ShowFolderCommentsAction::NAME => Actions\Comment\ShowFolderCommentsAction::class,
        Actions\Comment\ShowMediaCommentsAction::NAME => Actions\Comment\ShowMediaCommentsAction::class,
        // description
        Actions\Description\AddDescriptionAction::NAME => Actions\Description\AddDescriptionAction::class,
        // folder
        Actions\Folder\CreateFolderAction::NAME => Actions\Folder\CreateFolderAction::class,
        Actions\Folder\DeleteFolderAction::NAME => Actions\Folder\DeleteFolderAction::class,
        Actions\Folder\GetAllFolderInfosAction::NAME => Actions\Folder\GetAllFolderInfosAction::class,
        Actions\Folder\GetAllFoldersAction::NAME => Actions\Folder\GetAllFoldersAction::class,
        Actions\Folder\RemoveFolderAction::NAME => Actions\Folder\RemoveFolderAction::class,
        Actions\Folder\RenameFolderAction::NAME => Actions\Folder\RenameFolderAction::class,
        // media
        Actions\Media\AddMediaToFolderAction::NAME => Actions\Media\AddMediaToFolderAction::class,
        Actions\Media\GetAllMediaInFolderAction::NAME => Actions\Media\GetAllMediaInFolderAction::class,
        Actions\Media\RemoveMediaFromFolderAction::NAME => Actions\Media\RemoveMediaFromFolderAction::class,
        Actions\Media\TransferMediaAction::NAME => Actions\Media\TransferMediaAction::class,
        Actions\Media\GetPrevNextMediaInFolderAction::NAME => Actions\Media\GetPrevNextMediaInFolderAction::class,
        Actions\Media\GetMediaAdditionalInfosAction::NAME => Actions\Media\GetMediaAdditionalInfosAction::class,
        // share
        Actions\Share\AddForeignFolderAction::ADD => Actions\Share\AddForeignFolderAction::class,
        Actions\Share\SendEmailWithLinkAction::NAME => Actions\Share\SendEmailWithLinkAction::class,
        Actions\Share\ShowFolderShareAction::NAME => Actions\Share\ShowFolderShareAction::class,
        // sort
        Actions\Sort\MakeIndividualSortAction::NAME => Actions\Sort\MakeIndividualSortAction::class,
    ];


    /**
     * Authorizes user, throws exception when user is not authorized.
     *
     * @param Request         $request
     * @param LoggerInterface $logger
     * @param int             $userId
     * @param string          $token
     *
     * @return void
     * @throws HttpUnauthorizedException
     */
    private static function authorizeUser(Request $request, LoggerInterface $logger, int $userId, string $token): void
    {
        if (empty($token) === true) {
            $logger->debug("Token is empty.");
            throw new HttpUnauthorizedException($request);
        }

        $curlRequestArray = [
            CURLOPT_URL => "https://www.imago-images.de/imagoextern/php/oAuthServer/resource.php?access_token=$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $curlRequestArray);
        $curlResponse = curl_exec($curl);
        if ($curlResponse === false) {
            $logger->debug("Curl failed. Error: ".curl_error($curl));
            throw new HttpUnauthorizedException($request);
        }
        $curlResponseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($curlResponseHttpCode !== 200) {
            $logger->debug("Authorization returned status code $curlResponseHttpCode, expected 200.");
            throw new HttpUnauthorizedException($request);
        }
        // curl response is an array, with a weird format (not JSON). That is why we need to parse it here.
        $curlResponseArray = explode("\n", $curlResponse);
        foreach ($curlResponseArray as $line) {
            if (str_starts_with($line, "    [db_id] =>") === true) {
                $curlUserIdStr = trim(substr($line, strlen("    [db_id] =>")));
                $curlUserId    = intval($curlUserIdStr);
                $logger->debug("curlUserId:$curlUserId");
                break;
            }
        }
        if (isset($curlUserId) !== true) {
            $logger->debug("Could not find user id in curl response.");
            throw new HttpUnauthorizedException($request);
        }
        if ($curlUserId !== $userId) {
            $logger->debug("Token is valid for another user id. Received '$curlUserId' expected '$userId'.");
            throw new HttpUnauthorizedException($request, "Wrong credentials.");
        }
    }


    /**
     * Parses request, executes action and handles errors
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  http request that the api received
     * @param Response        $response empty http response
     *
     * @return Response
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

        // authorization
        if (isset($parsedRequest->getParsedBody()['userid']) === false) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, "Request did not contain a userid"), 400);
        }
        $userId = $parsedRequest->getParsedBody()['userid'];
        if (isset($parsedRequest->getParsedBody()['token']) === false) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, "Request did not contain a token"), 400);
        }
        $token = $parsedRequest->getParsedBody()['token'];
        try {
            self::authorizeUser($request, $logger, $userId, $token);
        } catch (HttpUnauthorizedException $httpUnauthorizedException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::UNAUTHENTICATED, "Authentication failed."),
                401
            );
        }

        // get action
        try {
            $action = parent::getActionFromJSON($parsedRequest, self::POST_ACTION_DICTIONARY);
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
        } catch (MediaUnknownException $mediaUnknownException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $mediaUnknownException->getMessage()),
                $mediaUnknownException->getCode()
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
