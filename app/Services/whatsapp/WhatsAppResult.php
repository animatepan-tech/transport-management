<?php

namespace App\Services\WhatsApp;

class WhatsAppResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?string $url = null,
    ) {
    }

    /**
     * Successful API/provider result.
     */
    public static function success(
        ?string $messageId = null,
        ?string $url = null
    ): self {
        return new self(
            success: true,
            messageId: $messageId,
            error: null,
            url: $url
        );
    }

    /**
     * Failed provider result.
     */
    public static function failure(
        string $error,
        ?string $messageId = null
    ): self {
        return new self(
            success: false,
            messageId: $messageId,
            error: $error,
            url: null
        );
    }
}