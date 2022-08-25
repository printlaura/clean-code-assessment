<?php

declare(strict_types=1);

namespace App\Authenticated\Controller\Actions;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for actions, that process the requests and responses
 */
abstract class Action extends \App\Unauthenticated\Controller\Actions\Action
{

    /**
     * OAuth2 Authentication token
     *
     * @var string
     */
    public static string $token;

    /**
     * User identification integer
     *
     * @var integer
     */
    public static int $userId;


    /**
     * Empty construct
     */
    public function __construct()
    {
        parent::__construct();
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
     * @param Request $request containing userId, token and language
     *
     * @return null
     * @throws RequestInValidException if userid, token or language not in $request
     */
    public static function setUserIdAndToken(Request $request)
    {
        parent::setLanguage($request);
        $body = $request->getParsedBody();
        ActionRequestValidation::containsPositiveIntegerValue($body, 'userid');
        ActionRequestValidation::containsKeys($body, 'body', ['token']);
        try {
            self::$userId = (int) $body['userid'];
            self::$token  = AbstractQueryBuilder::sanitizeInput($body['token']);
        } catch (Exception $exception) {
            throw new RequestInValidException($exception->getMessage(), 400, $exception);
        }
        return;
    }


}
