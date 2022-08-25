<?php

namespace App\Authenticated\Controller\Actions\Media;

use App\Authenticated\Controller\Actions\Action;
use App\Authenticated\Model\Models\Media\AddMediaToFolderModel;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for adding media to a folder
 */
class AddMediaToFolderAction extends Action
{
    const NAME = "add_media_to_folder";


    /**
     * Starts  @see AddMediaToFolderModel adds media using its id to a folder
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response folder id and media id
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user does not have edit rights
     * @throws UserUnknownException when user not found
     * @throws MediaUnknownException when media not found
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        ActionRequestValidation::containsObjectTypeImageOrVideo($content);
        // get values
        $folderId   = (int) $content['folderid'];
        $source     = $content['source'];
        $mediaId    = (int) $content['mediaid'];
        $objectType = $content['objecttype'];
        if ($objectType === 'image') {
            $mediaType = 'I';
        } else {
            $mediaType = 'V';
        }
        // test for rights
        $get = new GetFolderRightsModel($logger);
        $get->assertEdit(parent::$userId, $folderId);
        // execute action
        $addMediaToFolderModel = new AddMediaToFolderModel($logger);
        try {
            $addMediaToFolderModel->addMediaToFolder(parent::$userId, $folderId, new MediaReferenceModel($source, $mediaId, $mediaType));
        } catch (InvalidArgumentException $exception) {
            throw new RequestInValidException("Media type must be either 'I' or 'V' not $mediaType");
        }
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "add_media_to_folder",
                "content" => [
                    "folderid" => $folderId,
                    "mediaid" => $mediaId,
                    "source" => $source,
                    "objecttype" => $objectType,
                ],
            ]
        );
    }


}
