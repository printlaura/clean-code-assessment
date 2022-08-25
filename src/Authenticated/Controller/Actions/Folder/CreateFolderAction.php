<?php

namespace App\Authenticated\Controller\Actions\Folder;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Authenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Exceptions\RequestInValidException;
use App\Visiting\Exceptions\UserUnknownException;
use App\Authenticated\Model\Models\Folder\CreateFolderModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class creating a new folder
 */
class CreateFolderAction extends Action
{
    const NAME = "create_folder";

    public static function action(LoggerInterface $logger, Request $request, Response $response): Response
    {
        parent::setUserIdAndToken($request);
        // test values
        ActionRequestValidation::containsKeyContent($request->getParsedBody());
        $content = $request->getParsedBody()['content'];
        ActionRequestValidation::containsKeys($content, 'content', ['foldername']);
        // get values
        $folderName = AbstractQueryBuilder::sanitizeInput($content['foldername']);
        ActionRequestValidation::stringSize($folderName);
        // execute action
        $createFolderModel = new CreateFolderModel($logger);
        $folderId          = $createFolderModel->createFolder(parent::$userId, $folderName);
        // respond
        return parent::respondWithData(
            $response,
            [
                "action" => "create_folder",
                "content" => [
                    "folderid" => $folderId,
                    "foldername" => $folderName,
                ],
            ]
        );
    }

    // refactored

    


}
