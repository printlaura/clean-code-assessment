<?php

namespace App\Authenticated\Controller\Actions\Share;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Model\Models\Share\ShowFolderShareModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting @see ShareModel[] of a folder. To see who the folder is being shared with and with which level of access
 */
class ShowFolderShareAction extends Action
{
    const NAME = "show_folder_share";


    /**
     * Provides all users that have access to a shared folder and their rights (view/edit).
     * Starts @see ShowFolderShareModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response folder id and @see Share[] who the folder is being shared with and with which level of access
     * @throws RequestInValidException on invalid request
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');

        // execute action
        $folderId = (int) $content['folderid'];

        $showFolderShareModel = new ShowFolderShareModel($logger);
        $shares = $showFolderShareModel->getSharesFolder($folderId);

        return parent::respondWithData(
            $response,
            [
                "action" => "show_folder_share",
                "content" => [
                    "folderid" => $folderId,
                    "shareCount" => count($shares),
                    "shares" => $shares
                ],
            ]
        );
    }


}
