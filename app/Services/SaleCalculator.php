<?php

namespace App\Services;

use App\Enums\SellingMethod;
use App\Exceptions\CheckoutValidationException;
use InvalidArgumentException;

class SaleCalculator
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @return array{selling_method: string, requested_amount: ?string, requested_quantity: ?string, quantity: string, item_amount: string, calculated_value: string, rounding_adjustment: string} */
    public function line(string $sellingPrice, SellingMethod|string $method, string $input): array
    {
        try {
            $price = $this->math->normalize($sellingPrice, 2);
            $method = $method instanceof SellingMethod ? $method : SellingMethod::from($method);

            if ($this->math->compare($price, '0.00', 2) <= 0) {
                throw new CheckoutValidationException('The product selling price must be greater than zero.');
            }

            if ($method === SellingMethod::Amount) {
                $amount = $this->math->normalize($input, 2);

                if ($this->math->compare($amount, '0.00', 2) <= 0) {
                    throw new CheckoutValidationException('The requested amount must be greater than zero.');
                }

                $quantity = $this->math->divide($amount, 2, $price, 2, 3);

                if ($this->math->compare($quantity, '0.001', 3) < 0) {
                    throw new CheckoutValidationException('The requested amount is too small to produce the minimum quantity of 0.001 kg.');
                }

                $calculatedValue = $this->math->multiply($quantity, 3, $price, 2, 2);

                return [
                    'selling_method' => $method->value,
                    'requested_amount' => $amount,
                    'requested_quantity' => null,
                    'quantity' => $quantity,
                    'item_amount' => $amount,
                    'calculated_value' => $calculatedValue,
                    'rounding_adjustment' => $this->math->subtract($amount, $calculatedValue, 2),
                ];
            }

            $quantity = $this->math->normalize($input, 3);

            if ($this->math->compare($quantity, '0.001', 3) < 0) {
                throw new CheckoutValidationException('The quantity must be at least 0.001 kg.');
            }

            $amount = $this->math->multiply($quantity, 3, $price, 2, 2);

            return [
                'selling_method' => $method->value,
                'requested_amount' => null,
                'requested_quantity' => $quantity,
                'quantity' => $quantity,
                'item_amount' => $amount,
                'calculated_value' => $amount,
                'rounding_adjustment' => '0.00',
            ];
        } catch (InvalidArgumentException $exception) {
            throw new CheckoutValidationException($exception->getMessage(), previous: $exception);
        }
    }
}
