<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Session Middleware
 */
class SessionMiddleware implements Middleware
{


    /**
     * Starts session and adds it to the $request.
     *
     * @param Request        $request session will be added to
     * @param RequestHandler $handler will handle request once session was added
     *
     * @return Response handled request by $handler
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) === true) {
            session_start();
            $request = $request->withAttribute('session', $_SESSION);
        }

        return $handler->handle($request);
    }


}
