<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'logo_path',
        'phone',
        'email',
        'address',
        'receipt_footer_message',
        'currency_code',
        'default_low_stock_threshold',
        'show_logo_on_receipts',
    ];

    protected function casts(): array
    {
        return [
            'default_low_stock_threshold' => 'decimal:3',
            'show_logo_on_receipts' => 'boolean',
        ];
    }
}
