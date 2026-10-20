<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SendGridMailService
{
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $apiKey = config('services.sendgrid.api_key');
        $fromEmail = config('services.sendgrid.from_email');
        $fromName = config('services.sendgrid.from_name');

        if (empty($apiKey) || $apiKey === 'your-sendgrid-api-key-here') {
            Log::warning("SendGrid API key not configured. Skipping email to {$to}");
            return false;
        }

        $payload = json_encode([
            'personalizations' => [
                ['to' => [['email' => $to]]],
            ],
            'from' => [
                'email' => $fromEmail,
                'name' => $fromName,
            ],
            'subject' => $subject,
            'content' => [
                ['type' => 'text/html', 'value' => $htmlBody],
            ],
        ]);

        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("SendGrid cURL error: {$error}");
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        Log::error("SendGrid API error (HTTP {$httpCode}): {$response}");
        return false;
    }
}
