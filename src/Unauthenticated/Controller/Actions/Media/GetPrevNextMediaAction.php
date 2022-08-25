<?php

namespace App\Unauthenticated\Controller\Actions\Media;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\Models\Media\GetPrevNextMediaModel;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting previous and next media ids
 */
class GetPrevNextMediaAction extends Action
{
    const NAME = "prev_next_media";


    /**
     * Starts @see GetAllMediaInFolderModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response
     * @throws RequestInValidException on invalid request
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        $parsedRequest = $request->getParsedBody();
        $content       = $parsedRequest['content'];

        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        ActionRequestValidation::containsSource($content);


        $mediaId = (int) $content['mediaid'];
        $source  = (string) $content['source'];

        // execute action
        $mediaReference        = new MediaReferenceModel($source, $mediaId);
        $getPrevNextMediaModel = new GetPrevNextMediaModel();
        $infoPrevNext          = $getPrevNextMediaModel->get($mediaReference);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => self::NAME,
                "content" => $infoPrevNext,
            ]
        );
    }


}
