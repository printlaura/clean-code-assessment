<?php

namespace App\Visiting\Controller\Actions\Media;

use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\HashUnknownException;
use App\Visiting\Model\Models\Media\GetMediaAdditionalInfosModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;

/**
 * Class for getting additional media infos in a folder
 */
class GetMediaAdditionalInfosAction extends Action
{
    const NAME = "get_media_additional_infos";


    /**
     * Starts @see GetMediaAdditionalInfosModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response
     * @throws RequestInValidException on invalid request
     * @throws HashUnknownException
     * @throws MediaNotinFolderException when requested media is not present in folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setHash($request);
        $parsedRequest = $request->getParsedBody();
        $content       = $parsedRequest['content'];
        // test values
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        ActionRequestValidation::containsSource($content);

        $folderId = parent::getFolderId($logger);
        $mediaId  = (int) $content['mediaid'];
        $source   = $content['source'];

        // execute action
        $mediaReference = new MediaReferenceModel($source, $mediaId);
        // check if media exist in folder
        GetAllMediaInFolderModel::assertMediaExistsInFolder($logger, $folderId, $mediaReference);

        $getMediaAdditionalInfosModel = new GetMediaAdditionalInfosModel($logger);
        $additionalInfos      = $getMediaAdditionalInfosModel->get($folderId, $mediaReference);
        $hashOrFolder["hash"] = $parsedRequest['content']['hash'];
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
