<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailDeliveryService
{
    /**
     * Send email using the best available HTTPS channel (bypassing cloud SMTP port blocking).
     * Priority:
     * 1. Google Apps Script Web App (Sends directly from smartclaim.aeroart@gmail.com to ANY recipient)
     * 2. Resend HTTPS API (Port 443)
     * 3. Standard Laravel Mail
     *
     * @param string $to
     * @param Mailable $mailable
     * @return array ['success' => bool, 'is_restricted' => bool, 'message' => string]
     */
    public static function sendMailable(string $to, Mailable $mailable): array
    {
        // During unit testing, use standard Laravel Mail so Mail::fake() works
        if (app()->environment('testing')) {
            try {
                Mail::to($to)->send($mailable);

                return [
                    'success'       => true,
                    'is_restricted' => false,
                    'message'       => 'Delivered via Laravel Mail (Testing).',
                ];
            } catch (\Throwable $e) {
                return [
                    'success'       => false,
                    'is_restricted' => false,
                    'message'       => $e->getMessage(),
                ];
            }
        }

        // Render HTML & prepare subject
        try {
            $htmlContent = $mailable->render();
            $built = $mailable->build();
            $subject = $built->subject ?? 'SmartClaim Notification';
            $fromName = config('mail.from.name', 'SmartClaim System');
        } catch (\Throwable $e) {
            Log::error("EmailDeliveryService render error for {$to}: " . $e->getMessage());

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $e->getMessage(),
            ];
        }

        // 1. Check for Google Apps Script Webhook URL (Direct Gmail Delivery via Port 443)
        $gmailWebhookUrl = config('mail.gmail_webhook_url') ?: env('GMAIL_WEBHOOK_URL');
        if (!empty($gmailWebhookUrl)) {
            return self::sendViaGoogleScript($gmailWebhookUrl, $to, $subject, $htmlContent);
        }

        // 2. Check for Resend API Key
        $resendApiKey = config('mail.resend_api_key') ?: env('RESEND_API_KEY');
        if (!empty($resendApiKey)) {
            return self::sendViaResend($resendApiKey, $to, $subject, $htmlContent, $fromName);
        }

        // 3. Fallback to standard Laravel Mail
        try {
            Mail::to($to)->send($mailable);

            return [
                'success'       => true,
                'is_restricted' => false,
                'message'       => 'Delivered via standard Laravel Mail.',
            ];
        } catch (\Throwable $e) {
            Log::error("EmailDeliveryService Mail Error for {$to}: " . $e->getMessage());

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $e->getMessage(),
            ];
        }
    }

    /**
     * Deliver email through Google Apps Script HTTPS Web App.
     * This delivers legitimately as smartclaim.aeroart@gmail.com with NO domain restrictions.
     */
    protected static function sendViaGoogleScript(string $webhookUrl, string $to, string $subject, string $htmlContent): array
    {
        $client = new Client([
            'timeout'         => 15,
            'allow_redirects' => true,
        ]);

        try {
            $response = $client->post($webhookUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to'      => $to,
                    'subject' => $subject,
                    'html'    => $htmlContent,
                ],
            ]);

            $raw = $response->getBody()->getContents();
            $body = json_decode($raw, true);

            if ($response->getStatusCode() === 200 && isset($body['status']) && $body['status'] === 'success') {
                Log::info("EmailDeliveryService: Google Apps Script sent email from smartclaim.aeroart@gmail.com to {$to}");

                return [
                    'success'       => true,
                    'is_restricted' => false,
                    'message'       => 'Email sent directly from smartclaim.aeroart@gmail.com via Google Apps Script.',
                ];
            }

            Log::error("EmailDeliveryService: Google Apps Script responded with error: {$raw}");

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $body['message'] ?? 'Google Apps Script dispatch error',
            ];
        } catch (\Throwable $e) {
            Log::error("EmailDeliveryService: Google Apps Script connection error: " . $e->getMessage());

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $e->getMessage(),
            ];
        }
    }

    /**
     * Deliver email through Resend HTTPS API.
     */
    protected static function sendViaResend(string $apiKey, string $to, string $subject, string $htmlContent, ?string $fromName): array
    {
        $client = new Client(['timeout' => 10]);
        $fromEmail = env('RESEND_FROM', 'onboarding@resend.dev');
        $sender = "{$fromName} <{$fromEmail}>";

        try {
            $response = $client->post('https://api.resend.com/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($apiKey),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'from'    => $sender,
                    'to'      => [$to],
                    'subject' => $subject,
                    'html'    => $htmlContent,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            Log::info("EmailDeliveryService: Resend successfully delivered email to {$to}. ID: " . ($body['id'] ?? 'unknown'));

            return [
                'success'       => true,
                'is_restricted' => false,
                'message'       => 'Email sent successfully via Resend API.',
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            Log::warning("EmailDeliveryService: Resend ClientException for {$to}: {$errorBody}");

            if (str_contains($errorBody, 'only send testing emails to your own email address')) {
                return [
                    'success'       => false,
                    'is_restricted' => true,
                    'message'       => 'Resend testing domain only permits sending to account owner email.',
                ];
            }

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error("EmailDeliveryService: Resend unexpected error for {$to}: " . $e->getMessage());

            return [
                'success'       => false,
                'is_restricted' => false,
                'message'       => $e->getMessage(),
            ];
        }
    }
}
