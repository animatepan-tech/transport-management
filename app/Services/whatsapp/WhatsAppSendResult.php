<?php

namespace App\Services\WhatsApp;

class WhatsAppSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?string $url = null,
        public readonly array $data = [],
    ) {
    }

    public static function success(
        string $status = 'sent',
        ?string $messageId = null,
        ?string $url = null,
        array $data = []
    ): self {
        return new self(
            success: true,
            status: $status,
            messageId: $messageId,
            url: $url,
            data: $data
        );
    }

    public static function failure(
        string $error,
        string $status = 'failed',
        array $data = []
    ): self {
        return new self(
            success: false,
            status: $status,
            error: $error,
            data: $data
        );
    }
}