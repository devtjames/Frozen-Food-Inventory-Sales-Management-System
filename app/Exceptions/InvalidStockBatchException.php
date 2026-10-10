<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStockBatchException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected batch is not an available batch for this product.');
    }
}
