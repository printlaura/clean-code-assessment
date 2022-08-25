<?php

declare(strict_types=1);

use App\Unauthenticated\Controller\Middleware\AuthorizationMiddleware;
use App\Unauthenticated\Controller\Middleware\SessionMiddleware;
use App\Unauthenticated\Controller\Settings\Settings;
use Slim\App;

return function (App $app) {
    $app->add(new AuthorizationMiddleware(Settings::HTTP_AUTH_USER, Settings::HTTP_AUTH_PASS));
    $app->add(SessionMiddleware::class);
};
