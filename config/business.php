<?php

return [
    'profile_id' => 1,
    'logo_disk' => env('BUSINESS_LOGO_DISK', 'uploads'),
    'logo_directory' => 'business-logos',
    'logo_max_kilobytes' => (int) env('BUSINESS_LOGO_MAX_KB', 2048),
    'default_currency' => 'NGN',
    'default_low_stock_threshold' => '10.000',
];
