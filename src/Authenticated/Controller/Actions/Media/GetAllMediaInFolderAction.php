<?php

namespace App\Authenticated\Controller\Actions\Media;

use App\Authenticated\Controller\Actions\Action;
use App\Authenticated\Model\Models\User\GetFolderRightsModel;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\LicenseUnknownException;
use App\Unauthenticated\Exceptions\MediaSourceUnknownException;
use App\Unauthenticated\Exceptions\MediaUnknownException;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Visiting\Exceptions\UserMissingRightsException;
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
     * @throws UserMissingRightsException when user does not have edit rights
     * @throws MediaSourceUnknownException when media source not found
     * @throws MediaUnknownException when media not found
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        $parsedRequest = $request->getParsedBody();
        $content       = $parsedRequest['content'];
        ActionRequestValidation::containsPositiveIntegerValue($content, 'folderid');
        ActionRequestValidation::isOneOf($content, 'sortby', ['individual', 'added_date', 'created_date']);
        ActionRequestValidation::containsSortOrderLimitOffset($content);
        // test values
        $limit     = (int) $content['limit'];
        $offset    = (int) $content['offset'];
        $folderId  = (int) $content["folderid"];
        $sortBy    = AbstractQueryBuilder::sanitizeInput($content['sortby']);
        $sortOrder = AbstractQueryBuilder::sanitizeInput($content['sortorder']);
        // test for rights
        $get = new GetFolderRightsModel($logger);
        $get->assertView(parent::$userId, $folderId);
        // execute action
        $mediaCount = GetAllMediaInFolderModel::getMediaCount($logger, $folderId);
        $mediaArray = GetAllMediaInFolderModel::getAllMediaInFolder($logger, $folderId, $limit, $offset, $sortBy, $sortOrder, parent::$language);

        $preMergedArray     = [
            "mediaCount" => $mediaCount,
            "limit"  => $limit,
            "offset" => $offset,
            "media"  => $mediaArray,
        ];
        $hashOrFolder["id"] = $folderId;

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
