<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Folder\RenameFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for renaming a folder
 */
class RenameFolderAction extends Action
{
    const NAME = "rename_folder";


    /**
     * Renames a folder for all users (including shared).
     * Starts @see RenameFolderModel and renames folder.
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response renamed folders id and new name
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user does not have edit rights
     * @throws UserUnknownException when user not found
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsKeys($content, 'content', ['foldername']);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        // get values
        $folderId      = (int) $content['folderid'];
        $folderNewName = AbstractQueryBuilder::sanitizeInput($content['foldername']);
        ActionRequestValidation::stringSize($folderNewName);
        // execute action
        $renameFolderModel = new RenameFolderModel($logger);
        $renameFolderModel->renameFolder(parent::$userId, $folderId, $folderNewName);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "rename_folder",
                "content" => [
                    "folderid" => $folderId,
                    "foldername" => $folderNewName,
                ],
            ]
        );
    }


}
