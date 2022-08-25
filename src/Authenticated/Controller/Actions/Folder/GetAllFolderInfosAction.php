<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Authenticated\Controller\Actions\Action;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Model\Models\Folder\GetAllFolderInfosModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting information on a folder, including media previews @see FolderModel
 */
class GetAllFolderInfosAction extends Action
{
    const NAME = "get_all_folderinfos";


    /**
     * Provides data to display folder view with all details of the folder, and it's content.
     * Starts @see GetAllFolderInfosModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response @see Folder containing information on a folder including media previews
     * @throws RequestInValidException on invalid request
     * @throws FolderUnknownException when requested folder is not found
     * @throws UserMissingRightsException when user is missing rights to view folder
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
        // test for access rights
        $getFolderRightsModel = new GetFolderRightsModel($logger);
        $getFolderRightsModel->assertView(parent::$userId, $folderId);
        // execute action
        $folderInfos = GetAllFolderInfosModel::getAllFolderInfos($logger, $folderId, false);
        $rights      = $getFolderRightsModel->getFolderRights(parent::$userId, $folderId);
        $folderInfos->setRights($rights);

        $hashOrFolder["id"] = $folderId;
        $output = array_merge($hashOrFolder, (array) $folderInfos);

        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "get_all_folderinfos",
                "content" => $output,
            ]
        );
    }


}
