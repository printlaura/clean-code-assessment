<?php

namespace App\Visiting\Controller\Actions\Folder;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Controller\Actions\Action;
use App\Visiting\Exceptions\FolderUnknownException;
use App\Visiting\Exceptions\HashUnknownException;
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
     * @throws HashUnknownException
     * @throws FolderUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setHash($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];

        // get values
        $folderId = parent::getFolderId($logger);
        // execute action
        $folderInfos          = GetAllFolderInfosModel::getAllFolderInfos($logger, $folderId, true);
        $hashOrFolder["hash"] = $content['hash'];
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
