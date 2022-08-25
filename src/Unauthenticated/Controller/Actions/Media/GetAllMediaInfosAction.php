<?php

namespace App\Unauthenticated\Controller\Actions\Media;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaInfoModel;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting information on media.
 */
class GetAllMediaInfosAction extends Action
{
    const NAME = "get_all_mediainfos";


    /**
     * Provides data do display media details.
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response
     * @throws RequestInValidException
     * @throws LicenseUnknownException
     * @throws MediaSourceUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setLanguage($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        // get values
        $mediaId  = (int) $content['mediaid'];
        $source   = AbstractQueryBuilder::sanitizeInput($content['source']);
        $mediaRef = new MediaReferenceModel($source, $mediaId);
        // execute action
        $mediaInfos = MediaInfoModel::get($logger, $mediaRef, parent::$language, true);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => self::NAME,
                "content" => $mediaInfos,
            ]
        );
    }


}
