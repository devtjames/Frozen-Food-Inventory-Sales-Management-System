<?php

namespace App\Exceptions;

use RuntimeException;

class ProductPriceChangedException extends RuntimeException
{
    public function __construct(string $productName)
    {
        parent::__construct("The selling price for {$productName} changed. Remove and add the item again before checkout.");
    }
}
