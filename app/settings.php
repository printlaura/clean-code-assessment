<?php

/**
 * Settings
 */

declare(strict_types=1);

use App\Unauthenticated\Controller\Settings\Settings;
use App\Unauthenticated\Controller\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings object
    $containerBuilder->addDefinitions(
        [
            SettingsInterface::class => function () {
                if (Settings::IS_PRODUCTION === true) {
                    $displayErrorDetails = false;
                    $logErrorDetails     = false;
                    $logLevel            = Logger::ERROR;
                } else {
                    $displayErrorDetails = true;
                    $logErrorDetails     = true;
                    $logLevel            = Logger::DEBUG;
                }
                // TODO: turn of stack traces and detailed messages in the php log
                return new Settings(
                    [
                        'displayErrorDetails' => $displayErrorDetails,
                        'logError'            => true,
                        'logErrorDetails'     => $logErrorDetails,
                        'determineRouteBeforeAppMiddleware' => true,
                        'logger' => [
                            'name' => 'imago-backend-app',
                            'path' => isset($_ENV['docker']) === true ? 'php://stdout' : dirname(__DIR__).'/logs/app.log',
                            'level' => $logLevel,
                        ],
                    ]
                );
            },
        ]
    );
};
