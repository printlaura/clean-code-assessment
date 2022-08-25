<?php

namespace App\Authenticated\Controller\Actions\Comment;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Model\Models\Comment\ShowCommentsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Responds with all comments that belong to a media
 */
class ShowMediaCommentsAction extends Action
{
    const NAME = "show_media_comments";


    /**
     * Starts @see ShowCommentsModel responds with all comments that belong to a media
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
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        ActionRequestValidation::containsKeys($content, 'content', ['source']);
        ActionRequestValidation::containsSource($content);
        // get values
        $mediaId  = $content['mediaid'];
        $source   = $content['source'];
        $folderId = $content['folderid'];
        // execute action
        $mediaRef = new MediaReferenceModel($source, $mediaId);
        $comments = ShowCommentsModel::getAllComments($logger, $folderId, $mediaRef);
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
