<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Authenticated\Model\Models\Folder\GetAllFoldersModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting all folders belonging to user (all folders with view&edit rights) @see FolderModel[]
 */
class GetAllFoldersAction extends Action
{
    const NAME = "get_all_folders" ;


    /**
     * Starts @see GetAllFoldersModel responds with all folders of a user @see Folder[]
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     * @throws RequestInValidException on invalid request
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::isOneOf($content, 'sortby', ['name', 'update_date']);
        ActionRequestValidation::containsSortOrderLimitOffset($content);
        // get values
        $userId    = parent::$userId;
        $sortBy    = $content['sortby'];
        $sortOrder = $content['sortorder'];
        $limit     = (int) $content['limit'];
        $offset    = (int) $content['offset'];
        $language  = parent::$language;
        
        // execute action
        $getAllFolderModel = new GetAllFoldersModel($logger);
        $folders           = $getAllFolderModel->getAllFolders($userId, $sortBy, $sortOrder, $limit, $offset, $language);
        $folderCount       = $getAllFolderModel->getFolderCount($userId);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "get_all_folders",
                "folderCount" => $folderCount,
                "content" => $folders,
            ]
        );
    }


}
