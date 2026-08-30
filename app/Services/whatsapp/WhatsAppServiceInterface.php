<?php

namespace App\Services\WhatsApp;

interface WhatsAppServiceInterface
{
    /**
     * Send a WhatsApp message.
     */
    public function send(
        string $phone,
        string $message,
        array $options = []
    ): WhatsAppSendResult;

    /**
     * Generate a click-to-chat URL.
     */
    public function createChatUrl(
        string $phone,
        string $message
    ): string;

    /**
     * Return provider name.
     */
    public function provider(): string;
}