<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConfig extends Model
{
    protected $table = 'whatsapp_config';

    protected $fillable = [
        'business_account_id',
        'phone_number_id',
        'display_phone_number',
        'access_token',
        'api_version',
        'is_enabled',
        'connection_status',
        'last_connection_test_at',
        'last_connection_error',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_connection_test_at' => 'datetime',
    ];
}