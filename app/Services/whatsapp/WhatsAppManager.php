<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use App\Services\Providers\LocalWhatsAppProvider;
use App\Services\Providers\GupshupWhatsAppProvider;
use App\Services\Providers\MetaWhatsAppProvider;
use InvalidArgumentException;

class WhatsAppManager
{
    private WhatsAppProvider $provider;

    public function __construct()
    {
        $driver = config(
            'whatsapp.provider',
            'local'
        );

        $this->provider = $this->resolveProvider(
            $driver
        );
    }

    public function driver(): WhatsAppProvider
    {
        return $this->provider;
    }

    private function resolveProvider(
        string $driver
    ): WhatsAppProvider {
        return match ($driver) {

            'local' => app(
                LocalWhatsAppProvider::class
            ),

            'gupshup' => app(
                GupshupWhatsAppProvider::class
            ),

            'meta' => app(
                MetaWhatsAppProvider::class
            ),

            default => throw new InvalidArgumentException(
                "Unsupported WhatsApp provider: {$driver}"
            ),
        };
    }
}