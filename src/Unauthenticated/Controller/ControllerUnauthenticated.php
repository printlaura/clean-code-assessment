<?php

namespace App\Unauthenticated\Controller;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionError;
use App\Unauthenticated\Controller\Settings\RelativePaths;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use Slim\Logger;

/**
 * Controller to route requests to the responsible action.
 *
 * This class contains two types of execute Action. Please only use executeAction in the future.
 * executeJSONAction still exists from the time were we were distinguishing the type of action using a jsonbody and not the path of a request.
 */
class ControllerUnauthenticated
{
    const DELETE_ACTION_DICT = [];
    const GET_ACTION_DICT    = [
        // prices
        Actions\Prices\GetLicensegroupsAction::PATH => Actions\Prices\GetLicensegroupsAction::class,
        Actions\Prices\GetLicensesAction::PATH => Actions\Prices\GetLicensesAction::class,
        Actions\Prices\GetPackagesAction::PATH => Actions\Prices\GetPackagesAction::class,
    ];
    const POST_ACTION_DICT   = [
        // media
        Actions\Media\GetAllMediaInfosAction::NAME => Actions\Media\GetAllMediaInfosAction::class,
        Actions\Media\GetPrevNextMediaAction::NAME => Actions\Media\GetPrevNextMediaAction::class,
        // Actions\Media\GetSimilarMediaAction::NAME => Actions\Media\GetSimilarMediaAction::class,
    ];


    public static function executeAction($logger, Request $request, Response $response): Response
    {
        $logger->debug("Received Request: ".$request->getMethod()." ".$request->getUri()->getPath());
        // get action
        try {
            $action = self::getAction($logger, $request);
        } catch (Exception $exception) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
        }

        $requestWithParsedBody = self::getRequestWithParsedBody($request, $response, $logger);

        // execute action
        try {
            $response = $action->action($logger, $requestWithParsedBody, $response);
        } catch (Exception $exception) {
            // log exception
            $logger->error("Error when executing request. ".$exception->getMessage());
            if (isset($requestWithParsedBody) === true) {
                $logger->info("Request: ".json_encode($requestWithParsedBody->getParsedBody()));
            }
            $logger->info("Response 500 - {$exception->getMessage()}");
            $logger->info($exception);
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::SERVER_ERROR, $exception->getMessage())
            );
        }
        return $response;
        // response is 200
    }


    public static function getRequestWithParsedBody(Request $request, Response $response, LoggerInterface $logger): ServerRequestInterface
    {
        if ($request->getMethod() === "POST") {
            try {
                $requestWithParsedBody = self::parseRequestJSON($request);
            } catch (Exception $exception) {
                throw Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
            }
        } else {
            $requestWithParsedBody = $request->withParsedBody(null);
        }

        return $requestWithParsedBody;
    }

    /**
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
            $parsedRequestBody = self::parseRequestJSON($request);
        } catch (Exception $exception) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
        }
        $logger->debug($request->getBody());
        // get action
        try {
            $action = self::getActionFromJSON($parsedRequestBody, self::POST_ACTION_DICT);
        } catch (Exception $exception) {
            return Action::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $exception->getMessage()), 400);
        }

        // execute action
        try {
            $response = $action->action($logger, $parsedRequestBody, $response);
        } catch (MediaSourceUnknownException $mediaSourceUnknownException) {
            return ACtion::respondWithError($logger, $response, new ActionError(ActionError::VALIDATION_ERROR, $mediaSourceUnknownException->getMessage()), 400);
        } catch (MediaNotInFolderException $mediaNotInFolderException) {
            return Action::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::RESOURCE_NOT_FOUND, $mediaNotInFolderException->getMessage()),
                $mediaNotInFolderException->getCode()
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
            $logger->info("Request: ".json_encode($parsedRequestBody->getParsedBody()));
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


    /**
     * Gets Actions from action dictionary using http pattern and action name
     *
     * @param Request $parsedRequest  request that was parsed
     * @param array   $postActionDict dictionary of post actions
     *
     * @return mixed new Action::class
     * @throws RequestInValidException when $pattern is not POST or $actionName is not part of the action dictionary
     */
    public static function getActionFromJSON(Request $parsedRequest, array $postActionDict)
    {
        $actionDict = $postActionDict;
        // get action
        if (isset($parsedRequest->getParsedBody()['action']) === false) {
            throw new RequestInValidException("Request did not contain an action");
        }
        $actionName = $parsedRequest->getParsedBody()['action'];
        if (isset($actionDict[$actionName]) === false) {
            throw new RequestInValidException("Action '$actionName' unknown");
        }
        return new $actionDict[$actionName]();
    }


    public static function parseRequestJSON(Request $request): Request
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if ($contentType === 'application/json') {
            $input = file_get_contents('php://input');
            // remove leading 0 in integers
            $sanitisedJson = preg_replace('/(?<=:)\s*0+(?=[1-9])/', '', $input);

            $contents = json_decode($sanitisedJson, true);
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    return $request->withParsedBody($contents);
                case JSON_ERROR_DEPTH:
                    throw new Exception("Failed to decode JSON: Maximum stack depth exceeded");
                case JSON_ERROR_STATE_MISMATCH:
                    throw new Exception("Failed to decode JSON: Invalid or malformed JSON");
                case JSON_ERROR_CTRL_CHAR:
                    throw new Exception("Failed to decode JSON: Control character error");
                case JSON_ERROR_SYNTAX:
                    throw new Exception("Failed to decode JSON: Syntax error");
                case JSON_ERROR_UTF8:
                    throw new Exception("Failed to decode JSON: Malformed UTF-8 characters");
                default:
                    throw new Exception("Failed to decode JSON");
            }
        } else {
            throw new Exception("Content-Type not application/json");
        }
    }


    /**
     *
     * @param LoggerInterface $logger  logger reference
     * @param Request         $request
     *
     * @return Action
     * @throws HttpMethodNotAllowedException
     * @throws RequestInValidException on unknown path
     */
    private static function getAction(LoggerInterface $logger, Request $request): Action
    {
        $httpMethod = $request->getMethod();
        if ($httpMethod === "GET") {
            $actionDict = self::GET_ACTION_DICT;
        } else if ($httpMethod === "POST") {
            $actionDict = self::POST_ACTION_DICT;
        } else if ($httpMethod === "DELETE") {
            $actionDict = self::DELETE_ACTION_DICT;
        } else {
            throw new HttpMethodNotAllowedException($request);
        }


        $actionPath = str_replace("/".RelativePaths::getRootDirName()."/api/", "", $request->getUri()->getPath());
        if (isset($actionDict[$actionPath]) === false) {
            throw new RequestInValidException("Action path '$actionPath' unknown");
        }
        $logger->debug($actionDict[$actionPath]);
        return new $actionDict[$actionPath]();
    }

}
