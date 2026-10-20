<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;
use Throwable;

final class GraphQLException extends Exception implements ClientAware, ProvidesExtensions
{
    public function __construct(
        private readonly ErrorCode $errorCode,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: $errorCode->name, $errorCode->value, $previous);
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * @return array{code: int, name: string}
     */
    public function getExtensions(): array
    {
        return ['code' => $this->errorCode->value, 'name' => $this->errorCode->name];
    }
}
