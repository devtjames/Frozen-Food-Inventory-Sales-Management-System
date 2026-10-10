<?php

namespace App\Exceptions;

use RuntimeException;

class SaleAlreadyReversedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This sale has already been reversed.');
    }
}
