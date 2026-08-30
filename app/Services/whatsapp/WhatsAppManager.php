<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use App\Services\Providers\MetaWhatsAppProvider;

class WhatsAppManager
{
    /**
     * The single active WhatsApp provider.
     *
     * Production architecture:
     *
     * WhatsAppManager
     *      ↓
     * MetaWhatsAppProvider
     *      ↓
     * Meta WhatsApp Cloud API
     */
    private WhatsAppProvider $provider;

    public function __construct()
    {
        $this->provider = app(
            MetaWhatsAppProvider::class
        );
    }

    /**
     * Return the active WhatsApp provider.
     */
    public function driver(): WhatsAppProvider
    {
        return $this->provider;
    }
}