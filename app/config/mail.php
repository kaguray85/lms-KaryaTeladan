<?php
require_once __DIR__ . '/app.php';

function mailConfig(): array
{
    return [
        'host' => (string) env('SMTP_HOST', ''),
        'username' => (string) env('SMTP_USERNAME', ''),
        'password' => (string) env('SMTP_PASSWORD', ''),
        'port' => (int) env('SMTP_PORT', 587),
        'encryption' => (string) env('SMTP_ENCRYPTION', 'tls'),
        'from_email' => (string) env('SMTP_FROM_EMAIL', ''),
        'from_name' => (string) env('SMTP_FROM_NAME', APP_NAME),
    ];
}
