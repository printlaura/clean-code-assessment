<?php

declare(strict_types=1);

namespace App\Visiting\Controller\Actions;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Visiting\Exceptions\HashUnknownException;
use BadMethodCallException;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Abstract class for actions, that process the requests and responses
 */
abstract class Action extends \App\Unauthenticated\Controller\Actions\Action
{

    /**
     * Hash that is being used to acccess a folder
     *
     * @var string
     */
    public static string $hash;


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
     * @param Request $request containing userId, token amd language
     *
     * @return void
     * @throws RequestInValidException if userid, token or language no in $request
     */
    public static function setHash(Request $request)
    {
        parent::setLanguage($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        ActionRequestValidation::containsKeys($content, 'content', ['hash']);
        try {
            self::$hash = utf8_encode(AbstractQueryBuilder::sanitizeInput($content['hash']));
        } catch (Exception $exception) {
            throw new RequestInValidException($exception->getMessage(), 400, $exception);
        }
    }


    /**
     * Returns folder ID using hash
     *
     * @param LoggerInterface $logger logger
     *
     * @return int folderID
     * @throws BadMethodCallException setHash must have been called first
     * @throws HashUnknownException when hash was not found
     */
    public static function getFolderId(LoggerInterface $logger): int
    {
        if (isset(self::$hash) === false) {
            throw new BadMethodCallException("Hash was not set yet, please call setHash first");
        }
        $query = new QueryBuilderSelect();
        $query->select("id");
        $query->from("web_lb_folder");
        $query->andWhereEqualsBool("visible", true);
        $queryView = clone $query;
        $queryView->andWhereEqualsStr("viewhash", self::$hash);

        $database = new Database($logger);
        try {
            $resultFolder = $database->queryPreparedStatement($queryView, 1)[0];
            return (int) $resultFolder->id;
        } catch (UnexpectedValueException $e) {
            try {
                $queryEdit = clone $query;
                $queryEdit->andWhereEqualsStr("edithash", self::$hash);
                $resultFolder = $database->queryPreparedStatement($queryEdit, 1)[0];
                return (int) $resultFolder->id;
            } catch (UnexpectedValueException $e) {
                throw new HashUnknownException(self::$hash);
            }
        }
    }


}
