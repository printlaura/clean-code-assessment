<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Handlers;

use App\Unauthenticated\Controller\Actions\Action;
use App\Unauthenticated\Controller\Actions\ActionError;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\CallableResolverInterface;
use Throwable;

/**
 * Class for handling all http errors
 */
class HttpErrorHandler
{

    private bool $displayErrorDetails;

    private Throwable $exception;

    private ?LoggerInterface $logger;

    private ResponseFactoryInterface $responseFactory;

    private int $statusCode = 500;


    /**
     * Construct error handler.
     *
     * @param CallableResolverInterface $callableResolver
     * @param ResponseFactoryInterface  $responseFactory
     * @param LoggerInterface           $logger
     */
    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger          = $logger;
    }


    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request             The most recent Request object
     * @param Throwable              $exception           The caught Exception object
     * @param bool                   $displayErrorDetails Whether to display the error details
     * @param bool                   $logErrors           Whether to log errors
     * @param bool                   $logErrorDetails     Whether to log error details
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $this->displayErrorDetails = $displayErrorDetails;
        $this->exception           = $exception;

        if ($logErrors === true) {
            $logMessage = $exception->getMessage()." at ".$exception->getFile()." l".$exception->getLine();
            if ($logErrorDetails === true) {
                $logMessage .= "\nStacktrace:\n".$exception->getTraceAsString();
            }
            $this->logger->debug($logMessage);
        }

        return $this->respond();
    }


    /**
     * Respond with error message.
     *
     * @return Response
     */
    protected function respond(): Response
    {
        $actionError = new ActionError(
            ActionError::SERVER_ERROR,
            'An internal error has occurred while processing your request.'
        );

        if ($this->exception instanceof HttpException) {
            $actionError->setDescription($this->exception->getMessage());

            if ($this->exception instanceof HttpNotFoundException) {
                $actionError->setType(ActionError::RESOURCE_NOT_FOUND);
                $this->statusCode = 404;
            } else if ($this->exception instanceof HttpMethodNotAllowedException) {
                $actionError->setType(ActionError::NOT_ALLOWED);
                $this->statusCode = 401;
            } else if ($this->exception instanceof HttpUnauthorizedException) {
                $actionError->setType(ActionError::UNAUTHENTICATED);
                $this->statusCode = 401;
            } else if ($this->exception instanceof HttpForbiddenException) {
                $actionError->setType(ActionError::INSUFFICIENT_PRIVILEGES);
                $this->statusCode = 401;
            } else if ($this->exception instanceof HttpBadRequestException) {
                $actionError->setType(ActionError::BAD_REQUEST);
                $this->statusCode = 400;
            } else if ($this->exception instanceof HttpNotImplementedException) {
                $actionError->setType(ActionError::NOT_IMPLEMENTED);
                $this->statusCode = 501;
            }
        }

        if (($this->exception instanceof HttpException === false) && ($this->displayErrorDetails === true)) {
            $actionError->setDescription($this->exception->getMessage());
        }

        $response = $this->responseFactory->createResponse($this->statusCode);

        return Action::respondWithError($this->logger, $response, $actionError, $this->statusCode);
    }


}
