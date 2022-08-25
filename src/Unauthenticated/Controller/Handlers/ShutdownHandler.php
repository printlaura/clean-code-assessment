<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Handlers;

use App\Unauthenticated\Controller\ResponseEmitter\ResponseEmitter;
use App\Unauthenticated\Controller\Settings\SettingsInterface;
use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use DI\ContainerBuilder;
use Slim\Exception\HttpInternalServerErrorException;

/**
 * Class for handling shutdown of the api
 */
class ShutdownHandler
{

    /**
     * Lead to the error
     *
     * @var Request
     */
    private Request $request;

    /**
     * Handled the error
     *
     * @var HttpErrorHandler
     */
    private HttpErrorHandler $errorHandler;

    /**
     * True when details will get displayed
     *
     * @var boolean
     */
    private bool $displayErrorDetails;

    /**
     * Variable for the logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * ShutdownHandler constructor.
     *
     * @param Request          $request             request that lead to the error
     * @param HttpErrorHandler $errorHandler        handled the error and created shutdown
     * @param bool             $displayErrorDetails if details should get displayed
     * @param LoggerInterface  $logger              logs errors
     */
    public function __construct(
        Request $request,
        HttpErrorHandler $errorHandler,
        bool $displayErrorDetails,
        LoggerInterface $logger
    ) {
        $this->request      = $request;
        $this->errorHandler = $errorHandler;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->logger = $logger;
    }


    /**
     * Emits error response
     *
     * @return void
     */
    public function __invoke()
    {
        $error = error_get_last();
        if ($error !== null) {
            $errorFile    = $error['file'];
            $errorLine    = $error['line'];
            $errorMessage = $error['message'];
            $errorType    = $error['type'];
            $message      = 'An error while processing your request. Please try again later.';

            if ($this->displayErrorDetails === true) {
                switch ($errorType) {
                    case E_USER_ERROR:
                        $message  = "FATAL ERROR: $errorMessage. ";
                        $message .= " on line $errorLine in file $errorFile.";
                        break;
                    case E_USER_WARNING:
                        $message = "WARNING: $errorMessage";
                        break;
                    case E_USER_NOTICE:
                        $message = "NOTICE: $errorMessage";
                        break;
                    default:
                        $message  = "ERROR: $errorMessage";
                        $message .= " on line $errorLine in file $errorFile.";
                        break;
                }
            }

            try {
                $containerBuilder = new ContainerBuilder();
                $container        = $containerBuilder->build();
                $settings         = $container->get(SettingsInterface::class);
                $logError         = $settings->get('logError');
                $logErrorDetails  = $settings->get('logErrorDetails');
            } catch (Exception $exception) {
                $this->logger->critical("Exception on building container: ", [$exception]);
                $logError        = true;
                $logErrorDetails = false;
            }

            $exception = new HttpInternalServerErrorException($this->request, $message);
            $response  = $this->errorHandler->__invoke($this->request, $exception, $this->displayErrorDetails, $logError, $logErrorDetails);

            $this->logger->critical("$errorLine - $errorFile ".$message);

            $responseEmitter = new ResponseEmitter();
            $responseEmitter->emit($response);
        }
    }


}
