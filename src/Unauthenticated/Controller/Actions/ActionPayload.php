<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Actions;

use JsonSerializable;

/**
 * Class for handling action payloads
 */
class ActionPayload implements JsonSerializable
{

    /**
     * Status code of the response payload
     *
     * @var integer
     */
    private int $statusCode;

    /**
     * Error that will be responded with, if one occurred.
     *
     * @var ActionError|null
     */
    private ?ActionError $error;


    /**
     * New payload instance
     *
     * @param int              $statusCode of the response
     * @param ActionError|null $error      type of error, if one occurred
     */
    public function __construct(
        int          $statusCode = 200,
        ?ActionError $error = null
    ) {
        $this->statusCode = $statusCode;
        $this->error      = $error;
    }


    /**
     * Used for creating JSON response
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $payload = [
            'statusCode' => $this->statusCode,
        ];

        if ($this->error !== null) {
            $payload['error'] = $this->error;
        }

        return $payload;
    }


}
