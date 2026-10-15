<?php

namespace App\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;

class GraphQLException extends Exception implements ClientAware, ProvidesExtensions
{
    public function __construct(protected ErrorCode $errorCode, string $message = '')
    {
        parent::__construct($message ?: $errorCode->name, $errorCode->value);
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getExtensions(): array
    {
        return ['code' => $this->errorCode->value, 'name' => $this->errorCode->name];
    }
}