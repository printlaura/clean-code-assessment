<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Actions;

use JsonSerializable;

/**
 * Action Error Class
 */
class ActionError implements JsonSerializable
{

    public const BAD_REQUEST = 'BAD_REQUEST';
    public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const NOT_ALLOWED     = 'NOT_ALLOWED';
    public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    /**
     * Returned after error in routing.
     */
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const SERVER_ERROR       = 'SERVER_ERROR';
    public const UNAUTHENTICATED    = 'UNAUTHENTICATED';
    public const VALIDATION_ERROR   = 'VALIDATION_ERROR';
    // Unauthenticated const VERIFICATION_ERROR = 'VERIFICATION_ERROR';

    /**
     * Type of action error, should be one of the constants.
     *
     * @var string
     */
    private string $type;

    /**
     * Detailed description of the type and cause of error.
     *
     * @var string
     */
    private string $description;


    /**
     * Constructor for new Action Error.
     *
     * @param string      $type        Type of action error, should be one of the constants.
     * @param string|null $description Detailed description of the type and cause of error.
     */
    public function __construct(string $type, ?string $description)
    {
        $this->type        = $type;
        $this->description = $description;
    }


    /**
     * Returns the type of the error.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * Set error type, should be one of the constants.
     *
     * @param string $type new type of action error
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }


    /**
     * Set description of the error, should be detailed.
     *
     * @param string|null $description type of error, and it's cause.
     *
     * @return self
     */
    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }


    /**
     * Used for responding with this error.
     *
     * @return array dictionary
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
        ];
    }


    /**
     * Returns the description of the error.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }


    /**
     * String representation of ActionError for debugging
     *
     * @return string
     */
    public function __toString(): string
    {
        return "ActionError(type: $this->type, description: $this->description)";
    }


}
