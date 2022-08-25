<?php

namespace App\Authenticated\Controller\Actions\Activities;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Authenticated\Model\Models\Activities\MarkActivitiesModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class marking activities as read or unread
 */
class MarkActivitiesAction extends Action
{
    const NAME = "mark_activities";


    /**
     * Updates activities to mark them as read or unread.
     * Starts @see MarkActivitiesModel
     *
     * @param LoggerInterface $logger   logger reference
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response parsed response the server will return
     * @throws RequestInValidException on invalid request
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);

        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        // test input
        foreach ($content as $activity) {
            ActionRequestValidation::containsKeys($activity, 'activities', ['mark']);
            ActionRequestValidation::containsPositiveIntegerValue($activity, 'id');
            ActionRequestValidation::isOneOf($activity, 'mark', ['read', 'unread']);
        }
        // execute action
        $completed           = [];
        $markActivitiesModel = new MarkActivitiesModel($logger);
        foreach ($content as $activity) {
            $id   = (int) $activity['id'];
            $mark = $activity['mark'];

            if ($mark === 'read') {
                $markActivitiesModel->markRead(parent::$userId, $id);
            } else {
                $markActivitiesModel->markUnread(parent::$userId, $id);
            }
            $completed[] = [
                "id" => $id,
                "marked" => $mark,
            ];
        }
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "show_activities",
                "content" => ["activities" => $completed],
            ]
        );
    }


}
