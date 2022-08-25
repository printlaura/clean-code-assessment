<?php

namespace App\Authenticated\Controller\Actions\Comment;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Comment\AddCommentModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class adding a comment to media or folder
 */
class AddCommentAction extends Action
{
    const NAME   = "add_comment";
    const MEDIA  = "add_media_comment";
    const FOLDER = "add_folder_comment";


    /**
     * Starts @see AddCommentModel adds a comment to media or folder
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     * @throws RequestInValidException on invalid request
     * @throws UserUnknownException
     * @throws UserMissingRightsException when user is not allowed to view folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'objectid');
        ActionRequestValidation::containsObjectTypeMediaOrFolder($content);
        ActionRequestValidation::containsKeys($content, 'content', ['comment', 'folderid']);
        $objectType = $content['objecttype'];
        if ($objectType === "media") {
            ActionRequestValidation::containsSource($content);
            $source = $content['source'];
        } else {
            $source = null;
        }
        $objectId = (int) $content['objectid'];
        $comment  = AbstractQueryBuilder::sanitizeInput($content['comment']);
        ActionRequestValidation::stringSize($comment);
        $folderId = (int) $content['folderid'];
        if ($objectType === "media") {
            $mediaReference = new MediaReferenceModel($source, $objectId);
        } else {
            $mediaReference = null;
        }
        // execute action
        $addCommentModel = new AddCommentModel($logger);
        $commentId       = $addCommentModel->addComment(
            parent::$userId,
            $comment,
            $objectType,
            $mediaReference,
            $folderId
        );
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "add_comment",
                "content" => [
                    "objectid" => $objectId,
                    "source" => $source,
                    "objecttype" => $objectType,
                    "commentid" => $commentId,
                    "comment" => $comment,
                    "folderid" => $folderId
                ],
            ]
        );
    }


}
