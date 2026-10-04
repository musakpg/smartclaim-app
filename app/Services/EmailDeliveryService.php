<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailDeliveryService
{
    /**
     * Send email via Resend HTTP API (Port 443) when configured,
     * otherwise fallback to standard Laravel Mail.
     *
     * @param string $to
     * @param Mailable $mailable
     * @return array ['success' => bool, 'is_restricted' => bool, 'message' => string]
     */
    public static function sendMailable(string $to, Mailable $mailable): array
    {
        $resendApiKey = env('RESEND_API_KEY');

        // During unit testing or if Resend key is omitted, use standard Laravel Mail
        if (app()->environment('testing') || empty($resendApiKey)) {
            try {
                Mail::to($to)->send($mailable);

                return [
                    'success'       => true,
                    'is_restricted' => false,
                    'message'       => 'Delivered via Laravel Mail.',
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

        // Resend HTTP API (Port 443)
        try {
            $htmlContent = $mailable->render();
            $built = $mailable->build();
            $subject = $built->subject ?? 'SmartClaim Notification';
            $fromName = config('mail.from.name', 'SmartClaim System');

            return self::sendViaResend($resendApiKey, $to, $subject, $htmlContent, $fromName);
        } catch (\Throwable $e) {
            Log::error("EmailDeliveryService render error for {$to}: " . $e->getMessage());

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

            // Detect Resend free tier unverified domain restriction
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
