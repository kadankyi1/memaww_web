<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | FOR BUSINESS OPERATION
    |--------------------------------------------------------------------------
    |
    */
    'referral_discount_percentage' => env('REFERRAL_DISCOUNT_PERCENTAGE', 50),


    /*
    |--------------------------------------------------------------------------
    | PAYMENT_GATEWAY
    |--------------------------------------------------------------------------
    |
    */

    'payment_gateway_merchant_id' => env('PAYMENT_GATEWAY_MERCHANT_ID', ''),
    'payment_gateway_merchant_api_user' => env('PAYMENT_GATEWAY_MERCHANT_API_USER', ''),
    'payment_gateway_merchant_api_key' => env('PAYMENT_GATEWAY_MERCHANT_API_KEY', ''),
    'payment_gateway_merchant_test_api_key' => env('PAYMENT_GATEWAY_MERCHANT_TEST_API_KEY', ''),


    /*
    |--------------------------------------------------------------------------
    | FCM
    |--------------------------------------------------------------------------
    |
    */

    // SUPPORT EMAIL
    'fcm_server_key' => env('FCM_SERVER_KEY', ""),

    /*
    |--------------------------------------------------------------------------
    | DOLLAR RATES
    |--------------------------------------------------------------------------
    |
    */

    // SUPPORT EMAIL
    'one_dollar_to_one_ghana_cedi' => env('ONE_DOLLAR_ONE_GHANA_CEDI', 13.9),


    /*
    |--------------------------------------------------------------------------
    | My Variables
    |--------------------------------------------------------------------------
    |
    */

    // SUPPORT EMAIL
    'supportemail' => env('SUPPORT_EMAIL', 'support@memaww.com'),

    // ANDROID APP MINIMUM VERSION ALLOWED
    'androidminvc' => env('ANDROID_MIN_ALLOWED_VERSION_CODE', '1'),

    // iOS APP MINIMUM VERSION ALLOWED
    'iosminvc' => env('IOS_MIN_ALLOWED_VERSION_CODE', '1'),

    // ANDROID APP MAXIMUM VERSION ALLOWED
    'androidmaxvc' => env('ANDROID_MAX_VERSION_CODE', '3'),

    // iOS APP MAXIMUM VERSION ALLOWED
    'iosmaxvc' => env('IOS_MAX_VERSION_CODE', '1'),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
