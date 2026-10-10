<?php

return [
    'image_disk' => env('PRODUCT_IMAGE_DISK', 'uploads'),
    'image_directory' => 'product-images',
    'image_max_kilobytes' => (int) env('PRODUCT_IMAGE_MAX_KB', 2048),
];
