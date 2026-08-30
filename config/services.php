<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'msg91' => [

        'auth_key' => env('MSG91_AUTH_KEY'),

        'whatsapp_number' => env('MSG91_WHATSAPP_NUMBER'),

        'whatsapp_template' => env(
            'MSG91_WHATSAPP_TEMPLATE',
            'transport_due_v1'
        ),

        'whatsapp_language' => env(
            'MSG91_WHATSAPP_LANGUAGE',
            'en'
        ),

        'sms_template_id' => env('MSG91_SMS_TEMPLATE_ID'),
    ],

];