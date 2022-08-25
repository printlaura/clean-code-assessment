<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

/**
 * Handles authorization of the request sender via HTTP Basic Auth.
 * This prevents anyone from having accesses to this api, but only selected sources. For example our frontend.
 */
class AuthorizationMiddleware implements Middleware
{

    private string $password;

    private string $username;


    /**
     * Constructor
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }


    /**
     * Authorizes the request
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler when request passes, executes this
     *
     * @return ResponseInterface
     * @throws HttpUnauthorizedException on authorization failed
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('Authorization') === false) {
            throw new HttpUnauthorizedException($request, "Authorization of the request failed. Authorization header missing.");
        }

        $authString = $request->getHeader('Authorization')[0];
        if (str_contains($authString, "Basic") === false) {
            throw new HttpUnauthorizedException($request, "Authorization of the request failed.");
        }
        $authHash = str_replace("Basic ", "", $authString);

        $correctAuthHash = base64_encode("$this->username:$this->password");

        if ($authHash !== $correctAuthHash) {
            throw new HttpUnauthorizedException($request, "Authorization of the request failed. Wrong username or password.");
        }
        // request passed authorization
        return $handler->handle($request);
    }


}
