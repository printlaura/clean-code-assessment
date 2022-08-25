<?php

namespace App\Authenticated\Controller\Actions\Comment;

use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Model\Models\Comment\ShowCommentsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Responds with all comments that belong to a folder
 */
class ShowFolderCommentsAction extends Action
{
    const NAME = "show_folder_comments";


    /**
     * Provides all comments belonging to a folder.
     * Starts @see ShowCommentsModel responds with all comments that belong to a folder
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
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        // execute action
        $folderId          = (int) $content['folderid'];
        $showCommentsModel = new ShowCommentsModel($logger);
        $comments          = $showCommentsModel->getAllComments($logger, $folderId);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "show_comments",
                "content" => [
                    "commentCount" => count($comments),
                    "comments" => $comments
                ],
            ]
        );
    }


}
