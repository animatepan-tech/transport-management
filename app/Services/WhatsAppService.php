<?php

namespace App\Services;

use App\Models\Fee;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WhatsAppService
{
    /**
     * WhatsAppManager is the single provider entry point.
     *
     * Configuration comes from:
     *
     * .env
     *   ↓
     * config/whatsapp.php
     *   ↓
     * WhatsAppManager
     *   ↓
     * MetaWhatsAppProvider
     */
    public function __construct(
        private readonly WhatsAppManager $whatsapp
    ) {
    }

    /**
     * Test the currently configured WhatsApp connection.
     *
     * This method is retained temporarily for compatibility with
     * existing code. It no longer reads WhatsAppConfig from the DB.
     */
    public function testConnection(): array
    {
        $provider = $this->whatsapp->driver();

        $providerName = config(
            'whatsapp.provider',
            'local'
        );

        /*
        |--------------------------------------------------------------------------
        | Meta connection test
        |--------------------------------------------------------------------------
        */

        if ($providerName === 'meta') {

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

            if (!$phoneNumberId) {
                return [
                    'success' => false,
                    'message' =>
                        'Meta WhatsApp Phone Number ID is not configured.',
                    'error' =>
                        'WHATSAPP_PHONE_NUMBER_ID is missing.',
                ];
            }

            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' =>
                        'Meta WhatsApp access token is not configured.',
                    'error' =>
                        'WHATSAPP_ACCESS_TOKEN is missing.',
                ];
            }

            try {

                $url =
                    'https://graph.facebook.com/'
                    . $apiVersion
                    . '/'
                    . $phoneNumberId;

                $response = Http::withToken(
                    $accessToken
                )
                    ->acceptJson()
                    ->timeout(
                        (int) config(
                            'whatsapp.timeout',
                            30
                        )
                    )
                    ->get($url);

                if ($response->successful()) {

                    return [
                        'success' => true,
                        'message' =>
                            'WhatsApp connection successful.',
                        'data' =>
                            $response->json(),
                    ];
                }

                return [
                    'success' => false,
                    'message' =>
                        $response->json(
                            'error.message',
                            'WhatsApp connection failed.'
                        ),
                    'error' =>
                        $response->json(
                            'error.message',
                            'WhatsApp connection failed.'
                        ),
                    'data' =>
                        $response->json(),
                ];

            } catch (Throwable $e) {

                return [
                    'success' => false,
                    'message' =>
                        'Unable to connect to WhatsApp.',
                    'error' =>
                        $e->getMessage(),
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Non-Meta providers
        |--------------------------------------------------------------------------
        |
        | The active application should eventually use Meta only.
        | For compatibility, simply report that the configured
        | provider is available without performing a Meta test.
        |
        */

        return [
            'success' => true,
            'message' =>
                'WhatsApp provider is configured.',
            'provider' =>
                $providerName,
        ];
    }

    /**
     * Send a WhatsApp template message.
     *
     * Compatibility wrapper around the active WhatsApp provider.
     *
     * $components should contain the normal Meta template
     * component structure.
     */
    public function sendTemplate(
        string $recipient,
        string $templateName,
        string $languageCode = 'en',
        array $components = []
    ): array {
        $provider = $this->whatsapp->driver();

        /*
        |--------------------------------------------------------------------------
        | Convert Meta template components into provider parameters
        |--------------------------------------------------------------------------
        |
        | Existing legacy code may pass:
        |
        | [
        |     [
        |         'type' => 'body',
        |         'parameters' => [
        |             [
        |                 'type' => 'text',
        |                 'text' => 'Parent',
        |             ],
        |             ...
        |         ],
        |     ],
        | ]
        |
        | MetaWhatsAppProvider expects:
        |
        | 'parameters' => [
        |     'Parent',
        |     ...
        | ]
        |
        */

        $parameters = [];

        foreach ($components as $component) {

            if (
                !is_array($component)
                || ($component['type'] ?? null) !== 'body'
            ) {
                continue;
            }

            foreach (
                ($component['parameters'] ?? [])
                as $parameter
            ) {

                if (
                    is_array($parameter)
                    && ($parameter['type'] ?? null) === 'text'
                ) {

                    $parameters[] =
                        (string) (
                            $parameter['text'] ?? ''
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Provider call
        |--------------------------------------------------------------------------
        */

        return $provider->send(
            $recipient,
            '',
            [
                'type' =>
                    'template',

                'template' =>
                    $templateName,

                'language' =>
                    $languageCode,

                'parameters' =>
                    $parameters,
            ]
        );
    }

    /**
     * Send the current fee-due WhatsApp reminder.
     *
     * This method is retained temporarily because the existing
     * scheduled command calls it.
     *
     * The permanent scheduled-reminder implementation will be
     * redesigned in the later WhatsApp business-rule correction.
     */
    public function sendDueReminder(
        Fee $fee,
        float $balance
    ): array {

        $student = $fee->student;

        if (!$student) {

            return [
                'success' => false,
                'status' => 'failed',
                'template' => null,
                'message_id' => null,
                'error' =>
                    'Fee student relationship is not available.',
            ];
        }

        if (
            empty(
                $student->whatsapp_number
            )
        ) {

            return [
                'success' => false,
                'status' => 'failed',
                'template' => null,
                'message_id' => null,
                'error' =>
                    'Student does not have a WhatsApp number.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize phone
        |--------------------------------------------------------------------------
        */

        $phone = $this->normalizePhone(
            $student->whatsapp_number
        );

        if (!$phone) {

            return [
                'success' => false,
                'status' => 'failed',
                'template' => null,
                'message_id' => null,
                'error' =>
                    'Invalid WhatsApp phone number.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Template configuration
        |--------------------------------------------------------------------------
        */

        $templateName = config(
            'whatsapp.template',
            'transport_fee_due'
        );

        $language = config(
            'whatsapp.template_language',
            'en'
        );

        /*
        |--------------------------------------------------------------------------
        | Fee period
        |--------------------------------------------------------------------------
        */

        $periodStart = $fee->period_start
            ? $fee->period_start->format('d M Y')
            : '-';

        $periodEnd = $fee->period_end
            ? $fee->period_end->format('d M Y')
            : '-';

        $period =
            $periodStart
            . ' - '
            . $periodEnd;

        /*
        |--------------------------------------------------------------------------
        | Template parameters
        |--------------------------------------------------------------------------
        |
        | transport_fee_due:
        |
        | {{1}} Parent name
        | {{2}} Student name
        | {{3}} Fee period
        | {{4}} Outstanding amount
        |
        */

        $parameters = [
            trim(
                (string) (
                    $student->parent_name
                    ?: 'Parent'
                )
            ),

            trim(
                (string) $student->student_name
            ),

            $period,

            '₹' . number_format(
                $balance,
                2
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Send
        |--------------------------------------------------------------------------
        */

        try {

            $provider = $this->whatsapp->driver();

            $result = $provider->send(
                $phone,
                '',
                [
                    'type' =>
                        'template',

                    'template' =>
                        $templateName,

                    'language' =>
                        $language,

                    'parameters' =>
                        $parameters,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Provider failure
            |--------------------------------------------------------------------------
            */

            if (
                !($result['success'] ?? false)
            ) {

                return [
                    'success' => false,

                    'status' => 'failed',

                    'template' =>
                        $templateName,

                    'message_id' =>
                        $result['message_id']
                        ?? null,

                    'error' =>
                        $result['error']
                        ?? (
                            $result['message']
                            ?? 'WhatsApp sending failed.'
                        ),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            return [
                'success' => true,

                'status' => 'sent',

                'template' =>
                    $templateName,

                'message_id' =>
                    $result['message_id']
                    ?? null,

                'error' => null,
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,

                'status' => 'failed',

                'template' =>
                    $templateName,

                'message_id' => null,

                'error' =>
                    $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize an Indian WhatsApp number.
     */
    private function normalizePhone(
        ?string $phone
    ): ?string {

        if (!$phone) {
            return null;
        }

        $phone = preg_replace(
            '/[^0-9]/',
            '',
            $phone
        );

        if (!$phone) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 11 digit number beginning with 0
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 11
            && str_starts_with(
                $phone,
                '0'
            )
        ) {

            $phone = substr(
                $phone,
                1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Indian 10 digit mobile
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 10
            && preg_match(
                '/^[6-9][0-9]{9}$/',
                $phone
            )
        ) {

            return '91' . $phone;
        }

        /*
        |--------------------------------------------------------------------------
        | Indian international format
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 12
            && preg_match(
                '/^91[6-9][0-9]{9}$/',
                $phone
            )
        ) {

            return $phone;
        }

        return null;
    }
}