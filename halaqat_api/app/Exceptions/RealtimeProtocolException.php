<?php

namespace App\Exceptions;

use RuntimeException;

class RealtimeProtocolException extends RuntimeException
{
    public function __construct(public readonly string $codeName, string $message)
    {
        parent::__construct($message);
    }
}
