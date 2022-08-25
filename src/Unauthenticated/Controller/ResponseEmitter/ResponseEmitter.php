<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\ResponseEmitter;

use Psr\Http\Message\ResponseInterface;
use Slim\ResponseEmitter as SlimResponseEmitter;

/**
 * Custom response emitter
 */
class ResponseEmitter extends SlimResponseEmitter
{


    /**
     * Emits response and adds headers. This will respond to the server/user that the request was received from.
     *
     * @param ResponseInterface $response will be emitted
     *
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        // This variable should be set to the allowed host from which your API can be accessed with
        $origin = strval(($_SERVER['HTTP_ORIGIN'] ?? ''));

        $response = $response
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
            ->withHeader('Pragma', 'no-cache');

        if (ob_get_contents() === true) {
            ob_clean();
        }

        parent::emit($response);
    }


}
