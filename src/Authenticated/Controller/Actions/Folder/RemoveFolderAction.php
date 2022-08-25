<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Folder\RemoveFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for removing a folder
 */
class RemoveFolderAction extends Action
{
    const NAME = "remove_folder";


    /**
     * Only removes the folder for the user, so it's not visible anymore.
     * Other user can still interact with the folder and the data is being kept.
     * Starts @see RemoveFolderModel and removes folder.
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response removed folders id and name
     * @throws RequestInValidException on invalid request
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user is not allowed to view folder
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
        $removeFolderModel  = new RemoveFolderModel($logger);
        $removedFoldersName = $removeFolderModel->removeFolder(parent::$userId, $folderId);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "remove_folder",
                "content" => [
                    "folderid" => $folderId,
                    "foldername" => $removedFoldersName,
                ],
            ]
        );
    }


}
