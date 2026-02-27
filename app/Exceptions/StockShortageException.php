<?php

namespace App\Exceptions;

use RuntimeException;


class StockShortageException extends RuntimeException
{
    protected array $shortages;

    public function __construct(string $message, array $shortages = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->shortages = $shortages;
    }

    public function getShortages(): array
    {
        return $this->shortages;
    }
}