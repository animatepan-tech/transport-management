<?php

namespace App\Services\Providers;

use App\Contracts\WhatsAppProvider;

class LocalWhatsAppProvider implements WhatsAppProvider
{
    /**
     * Local provider does not directly send WhatsApp messages.
     *
     * It prepares a WhatsApp click-to-chat URL.
     */
    public function send(
        string $phone,
        string $message,
        array $options = []
    ): array {
        $phone = $this->normalizePhone($phone);

        if ($phone === null) {
            return [
                'success' => false,
                'provider' => 'local',
                'message_id' => null,
                'url' => null,
                'error' => 'Invalid WhatsApp phone number.',
            ];
        }

        $url = 'https://wa.me/'
            . $phone
            . '?text='
            . rawurlencode($message);

        return [
            'success' => true,
            'provider' => 'local',
            'message_id' => null,
            'url' => $url,
            'error' => null,
        ];
    }

    /**
     * Normalize Indian WhatsApp phone number.
     *
     * Accepted formats:
     * - 9876543210
     * - 09876543210
     * - +91 9876543210
     * - +91-9876543210
     * - 919876543210
     *
     * Returns:
     * - 919876543210
     * - null for invalid numbers
     */
    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        // Keep digits only.
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if ($phone === null || $phone === '') {
            return null;
        }

        // Remove leading zero from Indian local numbers.
        if (
            strlen($phone) === 11 &&
            str_starts_with($phone, '0')
        ) {
            $phone = substr($phone, 1);
        }

        // 10-digit Indian mobile number.
        if (
            strlen($phone) === 10 &&
            preg_match('/^[6-9][0-9]{9}$/', $phone)
        ) {
            return '91' . $phone;
        }

        // 12-digit Indian number with country code 91.
        if (
            strlen($phone) === 12 &&
            preg_match('/^91[6-9][0-9]{9}$/', $phone)
        ) {
            return $phone;
        }

        return null;
    }
}