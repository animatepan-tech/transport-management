<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class Msg91WhatsAppService
{
    protected string $authKey;
    protected string $integratedNumber;
    protected string $templateName;
    protected string $language;

    public function __construct()
    {
        $this->authKey = (string) config('services.msg91.auth_key');

        $this->integratedNumber = (string) config(
            'services.msg91.whatsapp_number'
        );

        $this->templateName = (string) config(
            'services.msg91.whatsapp_template',
            'transport_due_v1'
        );

        $this->language = (string) config(
            'services.msg91.whatsapp_language',
            'en'
        );
    }

    /**
     * Send an approved WhatsApp template through MSG91.
     *
     * @param string $recipientNumber
     * @param array $variables
     * @return array
     */
    public function sendTemplate(
        string $recipientNumber,
        array $variables = []
    ): array {
        if ($this->authKey === '') {
            throw new RuntimeException(
                'MSG91_AUTH_KEY is not configured.'
            );
        }

        if ($this->integratedNumber === '') {
            throw new RuntimeException(
                'MSG91_WHATSAPP_NUMBER is not configured.'
            );
        }

        $recipientNumber = $this->normalizePhoneNumber(
            $recipientNumber
        );

        $parameters = [];

        foreach ($variables as $value) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $value,
            ];
        }

        $payload = [
            'integrated_number' => $this->integratedNumber,

            'recipient_number' => $recipientNumber,

            'content_type' => 'template',

            'template' => [
                'name' => $this->templateName,

                'language' => [
                    'code' => $this->language,
                    'policy' => 'deterministic',
                ],

                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $parameters,
                    ],
                ],
            ],
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])
            ->post(
                'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/',
                $payload
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'MSG91 WhatsApp API error: ' .
                $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Convert phone number into international format.
     *
     * Examples:
     *
     * 9876543210
     * +919876543210
     * 919876543210
     *
     * become:
     *
     * 919876543210
     */
    protected function normalizePhoneNumber(
        string $number
    ): string {
        $number = preg_replace(
            '/[^0-9+]/',
            '',
            $number
        );

        if (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        }

        if (strlen($number) === 10) {
            $number = '91' . $number;
        }

        return $number;
    }
}