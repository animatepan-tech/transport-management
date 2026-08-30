<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class Msg91SmsService
{
    protected string $authKey;

    public function __construct()
    {
        $this->authKey = (string) config('services.msg91.auth_key');
    }

    /**
     * Send an SMS through MSG91.
     */
    public function send(
        string $mobile,
        string $message,
        string $senderId
    ): array {
        if ($this->authKey === '') {
            throw new RuntimeException(
                'MSG91_AUTH_KEY is not configured.'
            );
        }

        $mobile = $this->normalizePhoneNumber($mobile);

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])
            ->post(
                'https://control.msg91.com/api/v5/flow/',
                [
                    'template_id' => config(
                        'services.msg91.sms_template_id'
                    ),

                    'short_url' => '0',

                    'recipients' => [
                        [
                            'mobiles' => $mobile,
                            'var1' => $message,
                        ],
                    ],
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'MSG91 SMS API error: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Normalize Indian mobile number.
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