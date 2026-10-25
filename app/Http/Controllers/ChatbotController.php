<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Services\GeminiChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    protected GeminiChatbotService $geminiService;

    public function __construct(GeminiChatbotService $geminiService)
    {
        $this->geminiService = $geminiService;
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

        try {
            $result = $this->geminiService->chat(
                $history,
                $userMessage,
                $receiptAttachment,
                $sessionReceipt
            );

            // Retain sanitized conversation history in session
            session(['chatbot_history' => $result['history']]);

            return response()->json([
                'status' => 'success',
                'reply' => $result['reply'],
                'action_executed' => $result['action_executed'],
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatbotController chat invocation exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Clear and reset the conversation history for the current session.
     */
    public function resetSession(Request $request): JsonResponse
    {
        $request->session()->forget(['chatbot_history', 'chatbot_last_receipt']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chatbot conversation history reset successfully.',
        ]);
    }
}
