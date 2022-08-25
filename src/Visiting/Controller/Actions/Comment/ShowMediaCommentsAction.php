<?php

namespace App\Visiting\Controller\Actions\Comment;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\HashUnknownException;
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
     * @throws HashUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setHash($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        // get valuese
        $folderId = parent::getFolderId($logger);
        $source   = $content['source'];
        $mediaId  = $content['mediaid'];
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
