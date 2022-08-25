<?php

namespace App\Authenticated\Controller\Actions\Share;

use App\Authenticated\Controller\Actions\Action;
use App\Authenticated\Model\Models\Share\AddForeignFolderModel;
use App\Unauthenticated\Controller\Actions\ActionError;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\UserUnknownException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for adding a shared/foreign folder to the users folders
 */
class AddForeignFolderAction extends Action
{
    const ADD = "add_foreign_folder";
    const UPDATE_FOREIGN_FOLDER_RIGHTS = "update_foreign_folder_rights";


    /**
     * Starts @see AddForeignFolderModel addes folder that was shared with the user
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response newly created folder id
     * @throws RequestInValidException on invalid request
     * @throws UserUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsKeys($content, 'content', ['hash']);

        // execute action
        $hash = AbstractQueryBuilder::sanitizeInput($content['hash']);

        $addForeignFolderModel = new AddForeignFolderModel($logger);
        try {
            $folder = $addForeignFolderModel->addForeignFolder(parent::$userId, $hash);
        } catch (FolderUnknownException $e) {
            // TODO: turn into custom exception
            return self::respondWithError(
                $logger,
                $response,
                new ActionError(ActionError::NOT_ALLOWED, "User is not allowed to add this folder or hash is invalid."),
                405
            );
        }

        return parent::respondWithData(
            $response,
            [
                "action" => "add_foreign_folder",
                "content" => $folder,
            ]
        );
    }


}
