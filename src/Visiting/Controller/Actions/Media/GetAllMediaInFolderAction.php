<?php

namespace App\Visiting\Controller\Actions\Media;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Visiting\Controller\Actions\Action;
use App\Visiting\Exceptions\HashUnknownException;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for getting all media in a folder
 */
class GetAllMediaInFolderAction extends Action
{
    const NAME = "get_all_media_in_folder";


    /**
     * Starts GetAllMediaInFolderModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response
     * @throws RequestInValidException on invalid request
     * @throws LicenseUnknownException
     * @throws HashUnknownException
     * @throws MediaSourceUnknownException
     * @throws MediaUnknownException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setHash($request);
        $parsedRequest = $request->getParsedBody();
        $content       = $parsedRequest['content'];
        // test values
        $limit  = (int) $content['limit'];
        $offset = (int) $content['offset'];
        ActionRequestValidation::isOneOf($content, 'sortby', ['individual', 'added_date', 'created_date']);
        ActionRequestValidation::containsSortOrderLimitOffset($content);
        $sortBy    = AbstractQueryBuilder::sanitizeInput($content['sortby']);
        $sortOrder = AbstractQueryBuilder::sanitizeInput($content['sortorder']);
        $folderId  = self::getFolderId($logger);
        // execute action
        $mediaCount = GetAllMediaInFolderModel::getMediaCount($logger, $folderId);
        $mediaArray = GetAllMediaInFolderModel::getAllMediaInFolder($logger,  $folderId, $limit, $offset, $sortBy, $sortOrder, parent::$language);

        $preMergedArray       = [
            "mediaCount" => $mediaCount,
            "limit"  => $limit,
            "offset" => $offset,
            "media"  => $mediaArray,
        ];
        $hashOrFolder["hash"] = $parsedRequest['content']['hash'];
        $output = array_merge($hashOrFolder, $preMergedArray);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "get_all_media_in_folder",
                "content" => $output,
            ]
        );
    }


}
