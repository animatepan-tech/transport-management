<?php

namespace App\Services\Providers;

use App\Contracts\WhatsAppProvider;
use Illuminate\Support\Facades\Http;

class GupshupWhatsAppProvider implements WhatsAppProvider
{
    public function send(
        string $phone,
        string $message,
        array $options = []
    ): array {
        $response = Http::timeout(
            (int) config('whatsapp.timeout', 30)
        )
        ->asForm()
        ->withHeaders([
            'apikey' => config('whatsapp.api_key'),
        ])
        ->post(
            config('whatsapp.api_url'),
            [
                'channel' => 'whatsapp',
                'source' => config('whatsapp.source'),
                'destination' => $phone,
                'message' => json_encode([
                    'type' => 'text',
                    'text' => $message,
                ]),
                'src.name' => config('whatsapp.app_name'),
            ]
        );

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json() ?: $response->body(),
        ];
    }
}