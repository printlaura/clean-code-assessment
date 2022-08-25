<?php

namespace App\Authenticated\Controller\Actions\Activities;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserMissingRightsException;
use App\Authenticated\Model\Models\Activities\ShowActivitiesModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class showing activities of a user
 */
class ShowActivitiesAction extends Action
{
    const NAME = "show_activities";


    /**
     * Starts @see ShowActivitiesModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     * @throws RequestInValidException on invalid request
     * @throws UserMissingRightsException when user is not allowed to view folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test input
        ActionRequestValidation::containsKeys($content, 'content', ['objectid', 'objecttype', 'filter']);
        ActionRequestValidation::containsPositiveIntegerValue($content, 'objectid');
        ActionRequestValidation::isOneOf($content, 'objecttype', ['folder', 'all']);
        ActionRequestValidation::isOneOf($content, 'filter', ['', '"show_activities"']);

        // execute action
        $folderId   = null;
        $objectType = AbstractQueryBuilder::sanitizeInput($content['objecttype']);
        if ($objectType === 'folder') {
            $folderId = (int) $content['objectid'];
        }
        $onlyShowOtherUsersActivities = ($content['filter'] === 'other');
        // activities
        $showActivitiesModel = new ShowActivitiesModel($logger);
        $activities          = $showActivitiesModel->ShowActivities(parent::$userId, $folderId, $onlyShowOtherUsersActivities);
        // response
        return parent::respondWithData(
            $response,
            [
                "action" => "show_activities",
                "content" => [
                    "objectid" => $folderId,
                    "objecttype" => $objectType,
                    "activities" => $activities,
                ],
            ]
        );
    }


}
