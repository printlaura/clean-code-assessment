<?php

namespace App\Authenticated\Controller\Actions\Media;

use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Media\RemoveMediaFromFolderModel;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for removing media to a folder
 */
class RemoveMediaFromFolderAction extends Action
{
    const NAME = "remove_media_from_folder";


    /**
     * Starts RemoveMediaFromFolderModel removes media from folder. Can batch remove from multiple folders and or multiple media ids from the same folder
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response all the media ids that were removed and from which folder
     * @throws RequestInValidException on invalid request
     * @throws UserUnknownException when user not found
     * @throws UserMissingRightsException when user is not allowed to edit the folder
     * @throws MediaNotInFolderException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        foreach ($content as $item) {
            ActionRequestValidation::containsPositiveIntegerValue($item, 'folderid');
            ActionRequestValidation::containsPositiveIntegerValue($item, 'mediaid');
            ActionRequestValidation::containsSource($item);
        }
        // execute action
        $removeMediaFromFolderModel = new RemoveMediaFromFolderModel($logger);
        foreach ($content as $media) {
            $folderId = (int) $media['folderid'];
            $mediaId  = (int) $media['mediaid'];
            $source   = AbstractQueryBuilder::sanitizeInput($media['source']);
            // test for rights
            $get = new GetFolderRightsModel($logger);
            $get->assertEdit(parent::$userId, $folderId);

            $mediaReference = new MediaReferenceModel($source, $mediaId);
            // check for media existing in folder
            GetAllMediaInFolderModel::assertMediaExistsInFolder($logger, $folderId, $mediaReference);
            $removeMediaFromFolderModel->removeMediaFromFolder(parent::$userId, $folderId, $mediaReference);
        }
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "remove_media_from_folder",
                "content" => $content,
            ]
        );
    }


}
