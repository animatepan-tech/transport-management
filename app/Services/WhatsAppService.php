<?php

namespace App\Services;

use App\Models\WhatsAppConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    /**
     * Get the active WhatsApp configuration.
     */
    protected function config(): WhatsAppConfig
    {
        $config = WhatsAppConfig::where('is_enabled', true)
            ->latest('id')
            ->first();

        if (!$config) {
            throw new RuntimeException(
                'WhatsApp is not configured or is disabled.'
            );
        }

        return $config;
    }

    /**
     * Build the Meta Graph API URL.
     */
    protected function apiUrl(
        WhatsAppConfig $config,
        string $endpoint = ''
    ): string {
        return sprintf(
            'https://graph.facebook.com/%s/%s%s',
            $config->api_version,
            $config->phone_number_id,
            $endpoint
        );
    }

    /**
     * Test the configured WhatsApp phone number.
     */
    public function testConnection(): array
    {
        $config = $this->config();

        try {
            $response = Http::withToken($config->access_token)
                ->acceptJson()
                ->get(
                    $this->apiUrl($config)
                );

            if ($response->successful()) {
                $config->update([
                    'connection_status' => 'CONNECTED',
                    'last_connection_test_at' => now(),
                    'last_connection_error' => null,
                ]);

                return [
                    'success' => true,
                    'message' => 'WhatsApp connection successful.',
                    'data' => $response->json(),
                ];
            }

            $error = $response->json('error.message')
                ?? 'WhatsApp connection failed.';

            $config->update([
                'connection_status' => 'ERROR',
                'last_connection_test_at' => now(),
                'last_connection_error' => $error,
            ]);

            return [
                'success' => false,
                'message' => $error,
                'data' => $response->json(),
            ];

        } catch (\Throwable $e) {
            $config->update([
                'connection_status' => 'ERROR',
                'last_connection_test_at' => now(),
                'last_connection_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a WhatsApp template message.
     */
    public function sendTemplate(
        string $recipient,
        string $templateName,
        string $languageCode = 'en',
        array $components = []
    ): array {
        $config = $this->config();

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = Http::withToken($config->access_token)
            ->acceptJson()
            ->post(
                $this->apiUrl($config, '/messages'),
                $payload
            );

        if (!$response->successful()) {
            $error = $response->json('error.message')
                ?? 'WhatsApp message could not be sent.';

            throw new RuntimeException($error);
        }

        return $response->json();
    }
}