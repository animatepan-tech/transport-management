<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | Available:
    | local
    | meta
    |
    */

    'provider' => env(
        'WHATSAPP_PROVIDER',
        'local'
    ),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API
    |--------------------------------------------------------------------------
    */

    'access_token' => env(
        'WHATSAPP_ACCESS_TOKEN'
    ),

    'phone_number_id' => env(
        'WHATSAPP_PHONE_NUMBER_ID'
    ),

    'business_account_id' => env(
        'WHATSAPP_BUSINESS_ACCOUNT_ID'
    ),

    'api_version' => env(
        'WHATSAPP_API_VERSION',
        'v25.0'
    ),

    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Template
    |--------------------------------------------------------------------------
    */

    'template' => env(
        'WHATSAPP_TEMPLATE',
        'hello_world'
    ),

    'template_language' => env(
        'WHATSAPP_TEMPLATE_LANGUAGE',
        'en_US'
    ),

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */

    'phone' => env(
        'WHATSAPP_PHONE',
        '918080602041'
    ),

    'timeout' => env(
        'WHATSAPP_TIMEOUT',
        30
    ),

    /*
    |--------------------------------------------------------------------------
    | Legacy / Other Provider Settings
    |--------------------------------------------------------------------------
    |
    | Keep these for now because your existing application may still
    | contain Gupshup/other provider code.
    |
    */

    'api_url' => env(
        'WHATSAPP_API_URL'
    ),

    'api_key' => env(
        'WHATSAPP_API_KEY'
    ),

    'source' => env(
        'WHATSAPP_SOURCE',
        '918080602041'
    ),

    'app_name' => env(
        'WHATSAPP_APP_NAME',
        'transportmanagement'
    ),

];