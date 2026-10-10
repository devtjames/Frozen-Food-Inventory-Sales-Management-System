<?php

namespace App\Exceptions;

use RuntimeException;

class StockLossAlreadyReversedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This stock loss has already been reversed.');
    }
}
