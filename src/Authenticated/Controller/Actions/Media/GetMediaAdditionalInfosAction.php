<?php

namespace App\Authenticated\Controller\Actions\Media;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Model\Models\Media\GetMediaAdditionalInfosModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;

/**
 * Class for getting previous and next media ids in a folder
 */
class GetMediaAdditionalInfosAction extends Action
{
    const NAME = "get_media_additional_infos";


    /**
     * Starts @see GetAllMediaInFolderModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response folder id of the media, comment count, description
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user does not have view rights on destination folder
     * @throws MediaNotinFolderException when requested media is not present in folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        $parsedRequest = $request->getParsedBody();
        ActionRequestValidation::containsKeyContent($parsedRequest);
        $content = $parsedRequest['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        $folderId = (int) $content["folderid"];
        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        $mediaReference = new MediaReferenceModel($content['source'], (int) $content['mediaid']);
        // test for rights
        $get = new GetFolderRightsModel($logger);
        $get->assertView(parent::$userId, $folderId);
        // check if media exist in folder
        GetAllMediaInFolderModel::assertMediaExistsInFolder($logger, $folderId, $mediaReference);
        // execute action
        $getMediaAdditionalInfosModel = new GetMediaAdditionalInfosModel($logger);
        $additionalInfos          = $getMediaAdditionalInfosModel->get($folderId, $mediaReference);
        $hashOrFolder["folderid"] = $folderId;

        $output = array_merge($hashOrFolder, (array) $additionalInfos);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => self::NAME,
                "content" => $output,
            ]
        );
    }


}
