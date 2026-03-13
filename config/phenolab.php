<?php

return [
    'gbif' => [
        'api_base_url' => env('GBIF_API_BASE_URL', 'https://api.gbif.org/v1'),
        'timeout' => (int) env('GBIF_TIMEOUT', 10),
        'page_size' => (int) env('GBIF_PAGE_SIZE', 20),
    ],

    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    'upload' => [
        'max_image_size' => 10240, // KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_plan_types' => ['jpg', 'jpeg', 'png', 'svg', 'webp'],
    ],
];
