<?php

namespace IldarK\LaravelQueueRpc\Exceptions;

use Exception;
use Throwable;

class RpcException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @throws RpcException
     */
    public static function throw(int $httpCode, string $message = ''): static
    {
        throw new static($message, $httpCode);
    }
}
