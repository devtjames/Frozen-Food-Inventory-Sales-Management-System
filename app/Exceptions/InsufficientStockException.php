<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly string $requestedQuantity,
        public readonly string $availableQuantity,
    ) {
        parent::__construct("Insufficient stock. Requested {$requestedQuantity} kg but only {$availableQuantity} kg is available.");
    }
}
