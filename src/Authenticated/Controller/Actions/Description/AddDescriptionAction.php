<?php

namespace App\Authenticated\Controller\Actions\Description;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Description\AddDescriptionModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class adding description to a folder or media
 */
class AddDescriptionAction extends Action
{
    const NAME   = "add_description";
    const FOLDER = "add_folder_description";
    const MEDIA  = "add_media_description";


    /**
     * Add description to medium or folder.
     * Starts @see AddDescriptionModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     * @throws UserUnknownException
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user is not allowed to edit folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        ActionRequestValidation::containsKeys($content, 'content', ['description']);
        // get values
        $folderId    = (int) $content['folderid'];
        $description = AbstractQueryBuilder::sanitizeInput($content['description']);
        ActionRequestValidation::stringSize($description);
        $source         = null;
        $mediaId        = null;
        $mediaReference = null;
        // media
        if (isset($content['mediaid']) === true || isset($content['source']) === true) {
            // when either mediaid or source is set, the other one needs to be set too
            ActionRequestValidation::containsSource($content);
            ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
            $source         = $content['source'];
            $mediaId        = (int) $content['mediaid'];
            $mediaReference = new MediaReferenceModel($source, $mediaId);
        }
        // execute action
        $addDescriptionModel = new AddDescriptionModel($logger);
        $descriptionId       = $addDescriptionModel->addDescription(
            parent::$userId,
            $description,
            $folderId,
            $mediaReference
        );
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "add_description",
                "content" => [
                    "folderid" => $folderId,
                    "source" => $source,
                    "mediaid" => $mediaId,
                    "descriptionid" => $descriptionId,
                    "description" => $description
                ],
            ]
        );
    }


}
