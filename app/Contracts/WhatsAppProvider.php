<?php

namespace App\Contracts;

interface WhatsAppProvider
{
    /**
     * Send a WhatsApp message.
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
    ): array;
}