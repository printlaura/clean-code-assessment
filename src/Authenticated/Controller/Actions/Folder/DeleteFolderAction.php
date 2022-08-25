<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Folder\DeleteFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for deleting a folder. Can only be done if the user has editing rights
 */
class DeleteFolderAction extends Action
{
    const NAME = "delete_folder";


    /**
     * Starts @see DeleteFolderModel and deletes folder.
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response delete folders id and name
     * @throws RequestInValidException on invalid request
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user is not the folders owner
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        // get values
        $folderId = (int) $content['folderid'];
        // execute action
        $deleteFolderModel  = new DeleteFolderModel($logger);
        $deletedFoldersName = $deleteFolderModel->deleteFolder(parent::$userId, $folderId);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "delete_folder",
                "content" => [
                    "folderid" => $folderId,
                    "foldername" => $deletedFoldersName,
                ],
            ]
        );
    }


}
