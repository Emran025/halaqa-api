<?php

namespace App\Exceptions;

use RuntimeException;

class ApiConflictException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $codeName = 'conflict',
        public readonly ?string $resource = null,
        public readonly ?string $resourceId = null,
    ) {
        parent::__construct($message);
    }
}
