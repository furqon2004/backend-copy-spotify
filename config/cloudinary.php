<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | An HTTP or HTTPS URL to notify your application (a webhook) when
    | the process of uploads, deletes, and any API changes is completed.
    |
    */

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary URL
    |--------------------------------------------------------------------------
    |
    | The Cloudinary URL is a single string that contains all the
    | configuration parameters for your Cloudinary account.
    | Format: cloudinary://API_KEY:API_SECRET@CLOUD_NAME
    |
    */

    'cloud_url' => env('CLOUDINARY_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Upload Preset
    |--------------------------------------------------------------------------
    */

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Credentials (Alternative to CLOUDINARY_URL)
    |--------------------------------------------------------------------------
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),
    'api_key' => env('CLOUDINARY_API_KEY', ''),
    'api_secret' => env('CLOUDINARY_API_SECRET', ''),
];
