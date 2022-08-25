<?php

namespace App\Authenticated\Controller\Actions\Share;

use App\Authenticated\Controller\Actions\Action;
use App\Authenticated\Model\Models\Share\EmailToSendModel;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Visiting\Exceptions\FolderUnknownException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class for sending an invitation link to a shared folder
 */
class SendEmailWithLinkAction extends Action
{
    const NAME = "send_email_with_link";


    /**
     * Sends one E-Mail to each E-Mail address with sharing link.
     *
     * @param LoggerInterface $logger
     * @param Request         $request  parsed request that was received
     * @param Response        $response this response will be edited by the action and gets returned
     *
     * @return Response @see EmailToSend[]
     * @throws FolderUnknownException when requested folder is not found
     * @throws RequestInValidException on invalid request @see    EmailToSendModel to send invitation link to shared folder
     */
    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        $requestParsed = $request->getParsedBody();
        $content       = $requestParsed['content'];
        $userId        = $requestParsed['userid'];

        foreach ($content as $item) {
            ActionRequestValidation::containsKeys($item, 'content item', ['email', 'hash']);
        }

        $sentEmails = [];
        foreach ($content as $c) {
            $email       = AbstractQueryBuilder::sanitizeInput($c['email']);
            $hash        = AbstractQueryBuilder::sanitizeInput($c['hash']);
            $emailToSend = new EmailToSendModel($logger, parent::$language);
            $emailToSend->send($email, $hash, $userId);
            $sentEmails[] = $emailToSend;
        }

        return parent::respondWithData(
            $response,
            [
                "action" => "send_email_with_link",
                "content" => $sentEmails,
            ]
        );
    }


}
