<?php

namespace App\Services\WhatsApp;

interface WhatsAppProvider
{
    /**
     * Provider identifier.
     */
    public function provider(): string;

    /**
     * Send a WhatsApp message.
     *
     * @param string $phone
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function send(
        string $phone,
        string $message,
        array $context = []
    ): WhatsAppResult;
}