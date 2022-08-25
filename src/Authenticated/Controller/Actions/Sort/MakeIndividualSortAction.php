<?php

namespace App\Authenticated\Controller\Actions\Sort;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Sort\MakeIndividualSortModel;
use App\Unauthenticated\Exceptions\MediaNotInFolderException;
use App\Visiting\Model\Models\Media\GetAllMediaInFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class getting information on media in different sorting styles
 */
class MakeIndividualSortAction extends Action
{
    const NAME = "make_individual_sort";


    /**
     * Changes the sorting method of medium in a folder to "individual" and changes the position of a media.
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response folder id, object reference and new sort value
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user is not allowed to edit folder
     * @throws UserUnknownException when user not found
     * @throws MediaNotInFolderException
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test values
        ActionRequestValidation::containsIntegerValues($content, ['folderid', 'objectid', 'oldsort', 'newsort'], false);
        ActionRequestValidation::containsSource($content);
        ActionRequestValidation::containsObjectTypeImageOrVideo($content);
        // get values
        $folderId  = (int) $content['folderid'];
        $source    = AbstractQueryBuilder::sanitizeInput($content['source']);
        $mediaId   = (int) $content['objectid'];
        $mediaType = AbstractQueryBuilder::sanitizeInput($content['objecttype']);
        $mediaRef  = new MediaReferenceModel($source, $mediaId, $mediaType);
        $oldSort   = (int) $content['oldsort'];
        $newSort   = (int) $content['newsort'];
        if ($oldSort < 0) {
            throw new RequestInValidException("oldsort must be greater than or equal to 0");
        }
        if ($oldSort === $newSort) {
            throw new RequestInValidException("oldsort can't equal newsort");
        }
        // check if media exist in folder
        GetAllMediaInFolderModel::assertMediaExistsInFolder($logger, $folderId, $mediaRef);
        $mediaCount = GetAllMediaInFolderModel::getMediaCount($logger, $folderId);
        if ($mediaCount < $newSort || $mediaCount < $oldSort) {
            throw new RequestInValidException("Sort value can't be greater than amount of media.", 404);
        }
        // execute action
        $makeIndividualSortModel = new MakeIndividualSortModel($logger);
        $makeIndividualSortModel->makeIndividualSort(parent::$userId, $folderId, $mediaRef, $oldSort, $newSort);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "make_individual_sort",
                "content" => [
                    "objectid" => $mediaId,
                    "source" => $source,
                    "objecttype" => $mediaType,
                    "sort" => $newSort
                ],
            ]
        );
    }


}
