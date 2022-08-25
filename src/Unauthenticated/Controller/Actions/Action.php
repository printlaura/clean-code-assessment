<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Actions;

use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for actions, that process the requests and responses
 */
abstract class Action
{

    /**
     * Language
     *
     * @var string
     */
    public static string $language;


    /**
     * Empty construct
     */
    public function __construct()
    {
    }


    /**
     * Handles the request, gets executed by routes.php
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     */
    abstract public static function action(LoggerInterface $logger, Request $request, Response $response): Response;


    /**
     * Sets user id and token
     *
     * @param Request $request containing userId, token amd language
     *
     * @return void
     * @throws RequestInValidException if userid, token or language no in $request
     */
    public static function setLanguage(Request $request): void
    {
        $body = $request->getParsedBody();
        ActionRequestValidation::containsKeys($body, 'body', ['language']);
        try {
            self::$language = AbstractQueryBuilder::sanitizeInput($body['language']);
        } catch (Exception $exception) {
            throw new RequestInValidException($exception->getMessage(), 400, $exception);
        }
    }


    /**
     * Creates Response and encodes data to json
     *
     * @param Response   $response   will be edited and returned
     * @param array|null $data       will be encoded into json, default=null
     * @param int        $statusCode status code of the response, default=200
     *
     * @return Response response containing data and status code
     */
    public static function respondWithData(Response $response, array $data = null, int $statusCode = 200): Response
    {
        $payload = ['statuscode' => $statusCode];

        if ($data !== null) {
            $payload = array_merge($payload, $data);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT);
        // in order to format date time correctly please use App\Controller\Actions\CustomDateTime instead of DateTime
        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }


    /**
     * Create Response containing ActionError
     *
     * @param LoggerInterface $logger     for debugging
     * @param Response        $response   will be edited and returned
     * @param ActionError     $error      error that will be displayed
     * @param int             $statusCode status code of the response, default=500
     *
     * @return Response
     */
    public static function respondWithError(LoggerInterface $logger, Response $response, ActionError $error, int $statusCode = 500): Response
    {
        $logger->debug("RespondWithError: ".$error);
        return self::respondWithData($response, ['error' => $error], $statusCode);
    }


    /**
     * Create a response using a json data string. Only used for returning example json.
     *
     * @param Response    $response will be edited and returned
     * @param string|null $jsonData will be added to the response
     *
     * @return Response response containing data and status code 200
     */
    public static function respondWithExample(Response $response, string $jsonData = null): Response
    {
        $response->getBody()->write($jsonData);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }


    /**
     * Create a response using a json data string. Only used for returning example json.
     *
     * @param Response   $response   will be edited and returned
     * @param array|null $data       will be encoded into json, default=null
     * @param int        $statusCode status code of the response, default=200
     *
     * @return Response response containing data and status code
     */
    public static function respondWithStatus(Response $response, array $data = null, int $statusCode = 200): Response
    {

        $json = json_encode($data, JSON_PRETTY_PRINT);
        // in order to format date time correctly please use App\Controller\Actions\CustomDateTime instead of DateTime
        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }


}
