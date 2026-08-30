# Transport Management Laravel Starter

## Requirements
- PHP 8.2+
- Composer
- MySQL/MariaDB
- Laravel 12
- Windows/Linux/macOS

## Install
1. Extract this project.
2. Open terminal in the project root.
3. Run `composer install`.
4. Copy `.env.example` to `.env`.
5. Create database `transport_management`.
6. Run `php artisan key:generate`.
7. Run `php artisan migrate --seed`.
8. Run `php artisan serve --port=8001`.

Default login created by the seeder:
- Username: admin
- Password: admin123

IMPORTANT: The user requested a plain-text password for this single-user application. This is intentionally implemented without password hashing. It is not recommended for an Internet-facing production application.

## WhatsApp
WhatsApp is disabled by default. The provider integration must be configured before production. Do not claim messages are sent until a real WhatsApp Business API/provider is connected.

## Fee balance convention
- Negative = due
- Zero = paid
- Positive = advance

The starter includes the core fee/payment structure, but the final production version should add a dedicated advance-credit ledger, exact late-fee policy, QR payment integration/webhook, provider-specific WhatsApp templates, and full audit/reversal workflow.
