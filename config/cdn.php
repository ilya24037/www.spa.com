<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки CDN интеграции для оптимизации доставки статических файлов
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'provider' => env('CDN_PROVIDER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | CloudFlare CDN Settings
    |--------------------------------------------------------------------------
    */
    'cloudflare' => [
        'domain' => env('CLOUDFLARE_CDN_DOMAIN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'r2_bucket' => env('CLOUDFLARE_R2_BUCKET'),
        'image_resizing' => env('CLOUDFLARE_IMAGE_RESIZING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS CloudFront Settings  
    |--------------------------------------------------------------------------
    */
    'aws' => [
        'cloudfront_domain' => env('AWS_CLOUDFRONT_DOMAIN'),
        'distribution_id' => env('AWS_CLOUDFRONT_DISTRIBUTION_ID'),
        's3_bucket' => env('AWS_BUCKET'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'lambda_edge' => env('AWS_LAMBDA_EDGE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure CDN Settings
    |--------------------------------------------------------------------------
    */
    'azure' => [
        'domain' => env('AZURE_CDN_DOMAIN'),
        'profile_name' => env('AZURE_CDN_PROFILE'),
        'endpoint_name' => env('AZURE_CDN_ENDPOINT'),
        'resource_group' => env('AZURE_RESOURCE_GROUP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'max_file_size' => env('CDN_MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB

    'allowed_extensions' => [
        'images' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],
        'videos' => ['mp4', 'webm', 'mov', 'avi'],
        'documents' => ['pdf', 'doc', 'docx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization Settings
    |--------------------------------------------------------------------------
    */
    'image_optimization' => [
        'enabled' => env('CDN_IMAGE_OPTIMIZATION', true),
        'quality' => env('CDN_IMAGE_QUALITY', 85),
        'formats' => ['webp', 'avif', 'jpg'],
        'sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default_ttl' => env('CDN_CACHE_TTL', 86400), // 24 hours
        'image_ttl' => env('CDN_IMAGE_CACHE_TTL', 604800), // 7 days
        'video_ttl' => env('CDN_VIDEO_CACHE_TTL', 2592000), // 30 days
    ],
];