<?php

namespace App\Services\Providers;

use App\Contracts\WhatsAppProvider;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaWhatsAppProvider implements WhatsAppProvider
{
    /**
     * Send a WhatsApp message through Meta WhatsApp Cloud API.
     *
     * Supported modes:
     *
     * 1. Plain text:
     *
     * [
     *     'type' => 'text',
     * ]
     *
     * 2. Template:
     *
     * [
     *     'type' => 'template',
     *     'template' => 'transport_fee_due',
     *     'language' => 'en',
     *     'parameters' => [
     *         'Test Parent',
     *         'Test Student',
     *         '01 Aug 2026 - 31 Aug 2026',
     *         '₹1,200.00',
     *     ],
     * ]
     *
     * @param string $phone
     * @param string $message
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function send(
        string $phone,
        string $message,
        array $options = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Configuration
        |--------------------------------------------------------------------------
        */

        $phoneNumberId = config(
            'whatsapp.phone_number_id'
        );

        $accessToken = config(
            'whatsapp.access_token'
        );

        $apiVersion = config(
            'whatsapp.api_version',
            'v25.0'
        );

        $timeout = (int) config(
            'whatsapp.timeout',
            30
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Phone Number ID
        |--------------------------------------------------------------------------
        */

        if (empty($phoneNumberId)) {
            return [
                'success' => false,
                'message' =>
                    'Meta WhatsApp Phone Number ID is not configured.',
                'error' =>
                    'WHATSAPP_PHONE_NUMBER_ID is missing.',
                'provider' => 'meta',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Access Token
        |--------------------------------------------------------------------------
        */

        if (empty($accessToken)) {
            return [
                'success' => false,
                'message' =>
                    'Meta WhatsApp access token is not configured.',
                'error' =>
                    'WHATSAPP_ACCESS_TOKEN is missing.',
                'provider' => 'meta',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Recipient Phone Number
        |--------------------------------------------------------------------------
        */

        $phone = $this->normalizePhone($phone);

        if ($phone === null) {
            return [
                'success' => false,
                'message' =>
                    'Invalid WhatsApp phone number.',
                'error' =>
                    'Phone number could not be normalized.',
                'provider' => 'meta',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Message Type
        |--------------------------------------------------------------------------
        */

        $type = strtolower(
            (string) (
                $options['type']
                ?? 'template'
            )
        );

        if (!in_array(
            $type,
            ['text', 'template'],
            true
        )) {
            return [
                'success' => false,
                'message' =>
                    'Invalid Meta WhatsApp message type.',
                'error' =>
                    'Supported types are: text, template.',
                'provider' => 'meta',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Meta Graph API URL
        |--------------------------------------------------------------------------
        */

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $apiVersion,
            $phoneNumberId
        );

        /*
        |--------------------------------------------------------------------------
        | Build Payload
        |--------------------------------------------------------------------------
        */

        if ($type === 'text') {
            $payload = $this->buildTextPayload(
                $phone,
                $message,
                $options
            );
        } else {
            $payload = $this->buildTemplatePayload(
                $phone,
                $options
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send Request
        |--------------------------------------------------------------------------
        */

        try {
            $response = Http::withToken(
                $accessToken
            )
                ->acceptJson()
                ->timeout($timeout)
                ->post(
                    $url,
                    $payload
                );

            /*
            |--------------------------------------------------------------------------
            | Decode Response
            |--------------------------------------------------------------------------
            */

            $data = $response->json();

            /*
            |--------------------------------------------------------------------------
            | Successful Response
            |--------------------------------------------------------------------------
            */

            if ($response->successful()) {
                return [
                    'success' => true,

                    'message' =>
                        'WhatsApp message sent successfully.',

                    'message_id' => data_get(
                        $data,
                        'messages.0.id'
                    ),

                    'provider' => 'meta',

                    'data' => $data,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Meta API Error
            |--------------------------------------------------------------------------
            */

            return [
                'success' => false,

                'message' =>
                    'Meta WhatsApp API rejected the message.',

                'error' => data_get(
                    $data,
                    'error.message',
                    $response->body()
                ),

                'error_code' => data_get(
                    $data,
                    'error.code'
                ),

                'error_type' => data_get(
                    $data,
                    'error.type'
                ),

                'provider' => 'meta',

                'data' => $data,
            ];
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Laravel / HTTP Exception
            |--------------------------------------------------------------------------
            */

            return [
                'success' => false,

                'message' =>
                    'Unable to connect to Meta WhatsApp API.',

                'error' => $e->getMessage(),

                'provider' => 'meta',
            ];
        }
    }

    /**
     * Build plain-text message payload.
     *
     * IMPORTANT:
     * This is only appropriate when Meta allows a free-form text
     * message in the current conversation context.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildTextPayload(
        string $phone,
        string $message,
        array $options = []
    ): array {
        $previewUrl = (bool) (
            $options['preview_url']
            ?? false
        );

        return [
            'messaging_product' => 'whatsapp',

            'to' => $phone,

            'type' => 'text',

            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message,
            ],
        ];
    }

    /**
     * Build template message payload.
     *
     * Supported options:
     *
     * template
     * language
     * parameters
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildTemplatePayload(
        string $phone,
        array $options = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Template Name
        |--------------------------------------------------------------------------
        */

        $templateName = $options['template']
            ?? config(
                'whatsapp.template',
                'hello_world'
            );

        if (
            !is_string($templateName) ||
            trim($templateName) === ''
        ) {
            throw new \InvalidArgumentException(
                'WhatsApp template name is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Template Language
        |--------------------------------------------------------------------------
        */

        $templateLanguage = $options['language']
            ?? config(
                'whatsapp.template_language',
                'en_US'
            );

        /*
        |--------------------------------------------------------------------------
        | Base Template
        |--------------------------------------------------------------------------
        */

        $template = [
            'name' => $templateName,

            'language' => [
                'code' => $templateLanguage,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Body Parameters
        |--------------------------------------------------------------------------
        */

        $parameters = $options['parameters']
            ?? [];

        if (!is_array($parameters)) {
            $parameters = [];
        }

        if ($parameters !== []) {
            $bodyParameters = [];

            foreach ($parameters as $parameter) {
                $bodyParameters[] = [
                    'type' => 'text',
                    'text' => (string) $parameter,
                ];
            }

            $template['components'] = [
                [
                    'type' => 'body',

                    'parameters' => $bodyParameters,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Final Template Payload
        |--------------------------------------------------------------------------
        */

        return [
            'messaging_product' => 'whatsapp',

            'to' => $phone,

            'type' => 'template',

            'template' => $template,
        ];
    }

    /**
     * Normalize a WhatsApp phone number.
     *
     * Examples:
     *
     * +91 80806 02041
     * +918080602041
     * 918080602041
     * 8080602041
     *
     * become:
     *
     * 918080602041
     */
    private function normalizePhone(
        string $phone
    ): ?string {
        $phone = trim($phone);

        /*
        |--------------------------------------------------------------------------
        | Remove common formatting
        |--------------------------------------------------------------------------
        */

        $phone = preg_replace(
            '/[\s\-\(\)]/',
            '',
            $phone
        );

        if ($phone === null || $phone === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove leading +
        |--------------------------------------------------------------------------
        */

        if (str_starts_with(
            $phone,
            '+'
        )) {
            $phone = substr(
                $phone,
                1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Indian 10-digit number
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 10 &&
            ctype_digit($phone)
        ) {
            $phone = '91' . $phone;
        }

        /*
        |--------------------------------------------------------------------------
        | Digits Only
        |--------------------------------------------------------------------------
        */

        if (!ctype_digit($phone)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Basic International Length Check
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) < 10 ||
            strlen($phone) > 15
        ) {
            return null;
        }

        return $phone;
    }
}