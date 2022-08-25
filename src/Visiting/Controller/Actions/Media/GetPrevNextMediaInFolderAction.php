<?php

namespace App\Visiting\Controller\Actions\Media;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\HashUnknownException;
use App\Visiting\Model\Models\Media\GetPrevNextMediaInFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting previous and next media ids in a folder
 */
class GetPrevNextMediaInFolderAction extends Action
{
    const NAME = "prev_next_media_folder";


    /**
     * Starts @see GetPrevNextMediaInFolderModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response
     * @throws RequestInValidException on invalid request
     * @throws HashUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setHash($request);
        $parsedRequest = $request->getParsedBody();
        ActionRequestValidation::containsKeyContent($parsedRequest);
        $content = $parsedRequest['content'];
        // test values
        $folderId = parent::getFolderId($logger);


        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'mediaid');
        $mediaReference = new MediaReferenceModel($content['source'], (int) $content['mediaid']);
        ActionRequestValidation::isOneOf($content, 'sortby', ['individual', 'added_date', 'created_date']);
        ActionRequestValidation::containsKeys($content, 'content', ['sortorder']);
        $sortBy    = AbstractQueryBuilder::sanitizeInput($content['sortby']);
        $sortOrder = AbstractQueryBuilder::sanitizeInput($content['sortorder']);
        // execute action
        $getPrevNextMediaInFolderModel = new GetPrevNextMediaInFolderModel($logger);
        $infoPrevNext         = $getPrevNextMediaInFolderModel->get($folderId, $mediaReference, $sortBy, $sortOrder);
        $hashOrFolder["hash"] = $parsedRequest['content']['hash'];
        $output = (object) array_merge($hashOrFolder, (array) $infoPrevNext);

        // respond
        return parent::respondWithData(
            $response,
            [
                "action"  => self::NAME,
                "content" => $output,
            ]
        );
    }


}
