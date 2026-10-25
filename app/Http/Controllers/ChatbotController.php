<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Services\GeminiChatbotService;
use App\Services\ReceiptOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    protected GeminiChatbotService $geminiService;
    protected ReceiptOcrService $ocrService;

    public function __construct(GeminiChatbotService $geminiService, ReceiptOcrService $ocrService)
    {
        $this->geminiService = $geminiService;
        $this->ocrService = $ocrService;
    }

    /**
     * Handle incoming chatbot conversational prompt and optional multimodal receipt upload.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:3000',
            'receipt' => 'nullable|file|mimes:jpeg,png,webp,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $userMessage = $request->input('message');
        $receiptFile = $request->file('receipt');

        if (empty($userMessage) && !$receiptFile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide a message or upload a receipt image.',
            ], 422);
        }

        $receiptAttachment = null;

        if ($receiptFile) {
            try {
                // Cryptographic SHA-256 binary hash generation per fraud prevention guardrails
                $realPath = $receiptFile->getRealPath();
                $receiptHash = hash_file('sha256', $realPath);

                // Early Receipt Duplicate Fraud Detection on Upload (<30ms, save OCR & AI tokens)
                $duplicateClaim = Claim::where('receipt_image_hash', $receiptHash)->first();
                if ($duplicateClaim) {
                    $history = session('chatbot_history', []);
                    $recentHistoryText = collect($history)->pluck('parts')->flatten()->pluck('text')->implode(' ');
                    $fullContext = ($userMessage ?? '') . ' ' . $recentHistoryText;
                    $isMalay = (bool) preg_match('/(?:saya|nak|buat|tuntutan|resit|elaun|perjalanan|tindakan|terima|kasih|mohon|semak|bantu|petrol|tarikh)/i', $fullContext);

                    $claimUrl = route('claims.show', $duplicateClaim->claim_id);

                    $bmRefusal = "Untuk makluman anda, imej resit ini telah pun dihantar sebelum ini dan direkodkan di bawah baucar tuntutan [CLM-{$duplicateClaim->claim_id}]({$claimUrl}). Sistem pencegahan penipuan tidak membenarkan penghantaran semula resit yang sama bagi mengelakkan pertindihan tuntutan.\n\n⚡ Tindakan: Semak status tuntutan saya\n⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan";

                    $enRefusal = "Fraud Prevention Alert: This exact receipt image was already submitted under claim voucher [CLM-{$duplicateClaim->claim_id}]({$claimUrl}). Resubmission of duplicate receipts is prohibited by company audit policies.\n\n⚡ Action: What is the status of my recent claims?\n⚡ Action: I want to submit an expense receipt claim";

                    $refusal = $isMalay ? $bmRefusal : $enRefusal;

                    $mimeType = $receiptFile->getMimeType();
                    $base64Data = base64_encode(file_get_contents($realPath));

                    $uiMessages = session('chatbot_ui_messages', []);
                    $uiMessages[] = [
                        'id' => (int) (microtime(true) * 1000),
                        'sender' => 'user',
                        'text' => $userMessage ?? 'Uploaded receipt image',
                        'timestamp' => now()->format('h:i A'),
                        'image_preview' => "data:{$mimeType};base64,{$base64Data}",
                        'action' => null,
                    ];
                    $uiMessages[] = [
                        'id' => (int) (microtime(true) * 1000) + 1,
                        'sender' => 'bot',
                        'text' => $refusal,
                        'timestamp' => now()->format('h:i A'),
                        'action' => null,
                        'is_error' => false,
                    ];
                    session(['chatbot_ui_messages' => $uiMessages]);

                    return response()->json([
                        'status' => 'success',
                        'reply' => $refusal,
                        'duplicate_detected' => true,
                        'action_executed' => null,
                    ]);
                }

                // Store receipt binary safely in private disk storage
                $storedPath = $receiptFile->store('receipts', 'private');

                $mimeType = $receiptFile->getMimeType();
                $base64Data = base64_encode(file_get_contents($realPath));

                $receiptAttachment = [
                    'mime_type' => $mimeType,
                    'base64_data' => $base64Data,
                    'path' => $storedPath,
                    'hash' => $receiptHash,
                    'original_name' => $receiptFile->getClientOriginalName(),
                ];

                // Run Unified OCR & TF-IDF Prediction Pipeline via Google Cloud Vision
                try {
                    $ocrResult = $this->ocrService->extractReceiptData($receiptFile);
                    if (!empty($ocrResult['success'])) {
                        session([
                            'chatbot_ocr_baseline' => [
                                'predicted_category' => $ocrResult['category'] ?? $ocrResult['suggested_category'] ?? null,
                                'raw_text' => $ocrResult['raw_text'] ?? null,
                                'ocr_merchant' => $ocrResult['merchant'] ?? null,
                                'ocr_amount' => $ocrResult['amount'] ?? null,
                                'ocr_date' => $ocrResult['date'] ?? null,
                                'confidence' => $ocrResult['confidence'] ?? 88.50,
                            ]
                        ]);
                    }
                } catch (\Throwable $ocrEx) {
                    Log::warning('Chatbot ReceiptOcrService invocation warning: ' . $ocrEx->getMessage());
                }

                $receiptAttachment['ocr_baseline'] = session('chatbot_ocr_baseline');

                // Cache in session so follow-up confirmations can utilize the uploaded receipt
                session(['chatbot_last_receipt' => $receiptAttachment]);
            } catch (\Throwable $fileEx) {
                Log::error('Chatbot receipt processing failed: ' . $fileEx->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to process receipt image: ' . $fileEx->getMessage(),
                ], 200);
            }
        }

        $history = session('chatbot_history', []);
        $sessionReceipt = session('chatbot_last_receipt');
        $uiMessages = session('chatbot_ui_messages', []);

        $userUiMessage = [
            'id' => (int) (microtime(true) * 1000),
            'sender' => 'user',
            'text' => $userMessage ?? 'Uploaded receipt image',
            'timestamp' => now()->format('h:i A'),
            'image_preview' => $receiptAttachment ? "data:{$receiptAttachment['mime_type']};base64,{$receiptAttachment['base64_data']}" : null,
            'action' => null,
        ];

        // Early Out-of-Scope Backend Interception (<50ms, zero AI tokens spent)
        if ($this->geminiService->isExplicitlyOutOfScope($userMessage ?? '', !empty($receiptAttachment))) {
            $refusal = "Maaf, saya ialah SmartClaim Assistant yang dikhususkan untuk urusan tuntutan perbelanjaan, semakan resit, elaun perjalanan (mileage), dan audit syarikat sahaja. Saya tidak dapat menjawab topik di luar skop sistem ini. Ada sebarang urusan tuntutan yang boleh saya bantu?

⚡ Tindakan: Semak status tuntutan saya
⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan
⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)";

            $botUiMessage = [
                'id' => (int) (microtime(true) * 1000) + 1,
                'sender' => 'bot',
                'text' => $refusal,
                'timestamp' => now()->format('h:i A'),
                'action' => null,
                'is_error' => false,
            ];

            $uiMessages[] = $userUiMessage;
            $uiMessages[] = $botUiMessage;
            session(['chatbot_ui_messages' => $uiMessages]);

            return response()->json([
                'status' => 'success',
                'reply' => $refusal,
                'action_executed' => null,
            ]);
        }

        try {
            $result = $this->geminiService->chat(
                $history,
                $userMessage,
                $receiptAttachment,
                $sessionReceipt
            );

            // Retain sanitized conversation history in session
            session(['chatbot_history' => $result['history']]);

            $botUiMessage = [
                'id' => (int) (microtime(true) * 1000) + 1,
                'sender' => 'bot',
                'text' => $result['reply'],
                'timestamp' => now()->format('h:i A'),
                'action' => $result['action_executed'],
                'is_error' => false,
            ];

            $uiMessages[] = $userUiMessage;
            $uiMessages[] = $botUiMessage;
            session(['chatbot_ui_messages' => $uiMessages]);

            return response()->json([
                'status' => 'success',
                'reply' => $result['reply'],
                'action_executed' => $result['action_executed'],
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatbotController chat invocation exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $isTimeout = $e instanceof \Illuminate\Http\Client\ConnectionException
                || str_contains(strtolower($e->getMessage()), 'curl error 28');

            $errorMessage = $isTimeout
                ? 'The AI service took a little too long to respond while processing your request. Please try sending your message again.'
                : 'I encountered an error connecting to the Gemini AI service (' . $e->getMessage() . '). Please try again in a moment.';

            $botUiMessage = [
                'id' => (int) (microtime(true) * 1000) + 1,
                'sender' => 'bot',
                'text' => $errorMessage,
                'timestamp' => now()->format('h:i A'),
                'action' => null,
                'is_error' => true,
            ];

            $uiMessages[] = $userUiMessage;
            $uiMessages[] = $botUiMessage;
            session(['chatbot_ui_messages' => $uiMessages]);

            return response()->json([
                'status' => 'error',
                'message' => $isTimeout ? $errorMessage : $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Retrieve the current session's UI conversation turns and active receipt thumbnail.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $uiMessages = session('chatbot_ui_messages', []);
        $sessionReceipt = session('chatbot_last_receipt');
        $receiptPreview = !empty($sessionReceipt['base64_data']) && !empty($sessionReceipt['mime_type'])
            ? "data:{$sessionReceipt['mime_type']};base64,{$sessionReceipt['base64_data']}"
            : null;

        return response()->json([
            'status' => 'success',
            'messages' => $uiMessages,
            'has_active_receipt' => !empty($sessionReceipt),
            'session_receipt_preview' => $receiptPreview,
        ]);
    }

    /**
     * Clear and reset the conversation history for the current session.
     */
    public function resetSession(Request $request): JsonResponse
    {
        $request->session()->forget(['chatbot_history', 'chatbot_last_receipt', 'chatbot_ui_messages']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chatbot conversation history reset successfully.',
        ]);
    }
}
