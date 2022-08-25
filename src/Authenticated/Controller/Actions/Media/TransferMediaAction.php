<?php

namespace App\Authenticated\Controller\Actions\Media;

use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Folder\CreateFolderModel;
use App\Authenticated\Model\Models\Media\TransferMediaModel;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for moving and copying media between folders and creating new folders
 */
class TransferMediaAction extends Action
{
    const NAME = "transfer_media";


    /**
     * Starts TransferMediaModel moves and copies media between folders, can also create new folder
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response transfer type and destination folder id that might have been created
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user does not have edit rights on destination folder
     * @throws UserUnknownException when user not found
     * @throws MediaUnknownException
     * @throws MediaNotInFolderException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::isOneOf($content, 'transfertype', ['copy', 'move']);
        ActionRequestValidation::containsKeys($content, 'content', ['source', 'destination']);
        foreach ($content['source'] as $source) {
            ActionRequestValidation::containsPositiveIntegerValue($source, 'folderid');
            ActionRequestValidation::containsPositiveIntegerValue($source, 'objectid');
            ActionRequestValidation::containsSource($source);
        }
        ActionRequestValidation::containsPositiveIntegerValue($content['destination'], 'folderid');
        // get values
        $transferType        = $content['transfertype'];
        $destination         = $content['destination'];
        $destinationFolderId = (int) $destination['folderid'];
        if ($destinationFolderId === 0) {
            // create new folder
            ActionRequestValidation::containsKeys($content['destination'], 'destination', ['foldername']);
            $createFolderModel   = new CreateFolderModel($logger);
            $folderName          = AbstractQueryBuilder::sanitizeInput($destination['foldername']);
            $destinationFolderId = $createFolderModel->createFolder(parent::$userId, $folderName);
        }

        // test for rights
        $get = new GetFolderRightsModel($logger);
        $get->assertEdit(parent::$userId, $destinationFolderId);
        // execute action
        $transferMediaModel = new TransferMediaModel($logger);
        foreach ($content['source'] as $source) {
            $sourceFolderId       = (int) $source['folderid'];
            $sourceDatabaseSource = AbstractQueryBuilder::sanitizeInput($source['source']);
            $mediaId = (int) $source['objectid'];

            if ($transferType === 'copy') {
                $transferMediaModel->copyMedia(
                    parent::$userId,
                    new MediaReferenceModel($sourceDatabaseSource, $mediaId),
                    $sourceFolderId,
                    $destinationFolderId
                );
            } else if ($transferType === 'move') {
                // test for rights
                $get = new GetFolderRightsModel($logger);
                $get->assertEdit(parent::$userId, $sourceFolderId);
                $mediaReference = new MediaReferenceModel($sourceDatabaseSource, $mediaId);
                // check for media existing in folder
                GetAllMediaInFolderModel::assertMediaExistsInFolder($logger, $sourceFolderId, $mediaReference);
                $transferMediaModel->moveMedia(
                    parent::$userId,
                    $mediaReference,
                    $sourceFolderId,
                    $destinationFolderId
                );
            } else {
                throw new RequestInValidException("Transfer type '$transferType' unknown must be either 'copy' or 'move'.");
            }
        }
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "transfer_media",
                "content" => [
                    "objectid" => $content['source'][0]['objectid'],
                    "source" => $content['source'][0]['source'],
                    "transfertype" => $transferType,
                    "folderid" => $destinationFolderId
                ],
            ],
        );
    }


}
