<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatbotService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $endpoint;
    protected ChatbotActionService $actionService;

    public function __construct(ChatbotActionService $actionService)
    {
        $this->actionService = $actionService;
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        $this->endpoint = rtrim(config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
    }

    /**
     * Process conversational message and execute tool calls in a multi-turn chat.
     *
     * @param array $history Existing conversation history
     * @param string|null $userMessage Text prompt provided by user
     * @param array|null $receiptAttachment Uploaded receipt data (mime_type, base64_data, path, hash)
     * @param array|null $sessionReceipt Most recent receipt associated with conversation session
     * @return array [ 'reply' => string, 'history' => array, 'action_executed' => array|null ]
     */
    public function chat(array $history, ?string $userMessage, ?array $receiptAttachment = null, ?array $sessionReceipt = null): array
    {
        if (empty($this->apiKey)) {
            return [
                'reply' => "The Gemini API key is not configured in the system environment (`GEMINI_API_KEY`). Please contact your system administrator or set the key in `.env`.",
                'history' => $history,
                'action_executed' => null,
            ];
        }

        $user = Auth::user();
        $userParts = [];

        // Support multimodal receipt processing via inlineData
        if (!empty($receiptAttachment) && !empty($receiptAttachment['base64_data'])) {
            $userParts[] = [
                'inlineData' => [
                    'mimeType' => $receiptAttachment['mime_type'] ?? 'image/jpeg',
                    'data' => $receiptAttachment['base64_data'],
                ],
            ];
        }

        $promptText = trim((string) $userMessage);
        if ($promptText !== '') {
            $userParts[] = [
                'text' => $promptText,
            ];
        } elseif (!empty($receiptAttachment)) {
            $userParts[] = [
                'text' => 'Please analyze this uploaded receipt. Extract the merchant name, transaction date, total amount (in RM / MYR), and recommended expense category (e.g. Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive). Summarize the details and ask if I would like you to submit this claim voucher for audit.',
            ];
        }

        if (empty($userParts)) {
            return [
                'reply' => 'Please provide a message or attach a receipt image to proceed.',
                'history' => $history,
                'action_executed' => null,
            ];
        }

        // Append current user message turn to conversation history
        $history[] = [
            'role' => 'user',
            'parts' => $userParts,
        ];

        // Format history for Gemini API: remove local metadata like 'path' and 'hash' from inlineData if any
        $sanitizedHistory = $this->sanitizeHistoryForApi($history);

        $systemInstruction = $this->buildSystemInstruction($user);
        $tools = $this->getToolsDeclaration();

        $url = "{$this->endpoint}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $sanitizedHistory,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'tools' => $tools,
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                $errorData = $response->json('error.message') ?? 'Unknown API response error.';
                return [
                    'reply' => "I encountered an error connecting to Gemini AI: {$errorData}",
                    'history' => $history,
                    'action_executed' => null,
                ];
            }

            $responseData = $response->json();
            $candidate = $responseData['candidates'][0] ?? null;

            if (!$candidate || empty($candidate['content']['parts'])) {
                return [
                    'reply' => "I did not receive a legible response from Gemini. Please try again.",
                    'history' => $history,
                    'action_executed' => null,
                ];
            }

            $modelParts = $candidate['content']['parts'];
            $functionCallPart = null;

            foreach ($modelParts as $part) {
                if (isset($part['functionCall']) || isset($part['function_call'])) {
                    $functionCallPart = $part['functionCall'] ?? $part['function_call'];
                    break;
                }
            }

            // If Gemini issued a Function Call tool invocation
            if ($functionCallPart) {
                $toolName = $functionCallPart['name'];
                $toolArgs = $functionCallPart['args'] ?? [];

                // Delegate execution to ChatbotActionService with strict RBAC
                $activeReceipt = $receiptAttachment ?? $sessionReceipt;
                $actionResult = $this->actionService->executeAction($toolName, $toolArgs, $activeReceipt);

                // Append model's tool call turn to history
                $modelTurn = [
                    'role' => 'model',
                    'parts' => [
                        [
                            'functionCall' => [
                                'name' => $toolName,
                                'args' => (object) $toolArgs,
                            ],
                        ],
                    ],
                ];
                $history[] = $modelTurn;
                $sanitizedHistory[] = $modelTurn;

                // Append user's tool response turn to history
                $functionResponseTurn = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $toolName,
                                'response' => [
                                    'name' => $toolName,
                                    'content' => $actionResult,
                                ],
                            ],
                        ],
                    ],
                ];
                $history[] = $functionResponseTurn;
                $sanitizedHistory[] = $functionResponseTurn;

                // Call Gemini again with function output so the model can generate a conversational response
                $secondPayload = [
                    'contents' => $sanitizedHistory,
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction],
                        ],
                    ],
                    'tools' => $tools,
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 2048,
                    ],
                ];

                $secondResponse = Http::timeout(60)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $secondPayload);

                $conversationalReply = "I have executed the requested action: {$toolName}.";

                if ($secondResponse->successful()) {
                    $secondCandidate = $secondResponse->json('candidates.0');
                    if (!empty($secondCandidate['content']['parts'])) {
                        $replyTexts = [];
                        foreach ($secondCandidate['content']['parts'] as $p) {
                            if (!empty($p['text'])) {
                                $replyTexts[] = $p['text'];
                            }
                        }
                        if (!empty($replyTexts)) {
                            $conversationalReply = implode("\n\n", $replyTexts);
                        }
                    }
                }

                // Add model's final conversational reply to history
                $history[] = [
                    'role' => 'model',
                    'parts' => [
                        ['text' => $conversationalReply],
                    ],
                ];

                $actionBadge = [
                    'tool' => $toolName,
                    'status' => $actionResult['status'] ?? 'unknown',
                    'label' => $this->getActionLabel($toolName, $actionResult),
                    'data' => $actionResult,
                ];

                return [
                    'reply' => $conversationalReply,
                    'history' => $history,
                    'action_executed' => $actionBadge,
                ];
            }

            // Normal conversational text reply without tool invocation
            $replyTexts = [];
            foreach ($modelParts as $part) {
                if (!empty($part['text'])) {
                    $replyTexts[] = $part['text'];
                }
            }

            $finalReply = !empty($replyTexts)
                ? implode("\n\n", $replyTexts)
                : "I processed your request, but have no text reply.";

            $history[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => $finalReply],
                ],
            ];

            return [
                'reply' => $finalReply,
                'history' => $history,
                'action_executed' => null,
            ];

        } catch (\Throwable $e) {
            Log::error('GeminiChatbotService Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'reply' => "An unexpected error occurred while communicating with Gemini AI: " . $e->getMessage(),
                'history' => $history,
                'action_executed' => null,
            ];
        }
    }

    /**
     * Provide clean function calling tool declarations adhering to Google Generative Language schema.
     */
    public function getToolsDeclaration(): array
    {
        return [
            [
                'functionDeclarations' => [
                    [
                        'name' => 'submit_claim',
                        'description' => 'Submit a new official expense claim reimbursement request for the authenticated staff user.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'category' => [
                                    'type' => 'STRING',
                                    'description' => 'Expense category (e.g., Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive, Utilities).',
                                ],
                                'amount' => [
                                    'type' => 'NUMBER',
                                    'description' => 'The numeric total amount of the claim in Malaysian Ringgit (MYR / RM).',
                                ],
                                'merchant_name' => [
                                    'type' => 'STRING',
                                    'description' => 'The merchant, shop, or vendor name where the transaction took place.',
                                ],
                                'receipt_date' => [
                                    'type' => 'STRING',
                                    'description' => 'The transaction date in YYYY-MM-DD format.',
                                ],
                                'description' => [
                                    'type' => 'STRING',
                                    'description' => 'Business purpose or explanation for the expenditure.',
                                ],
                            ],
                            'required' => ['category', 'amount', 'merchant_name', 'description'],
                        ],
                    ],
                    [
                        'name' => 'check_claim_status',
                        'description' => 'Check the status and details of expense claims. Staff can check their own submissions. Finance and Managers can inspect any claim by ID or view recent submissions.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'STRING',
                                    'description' => 'Optional numeric ID of the claim voucher (e.g., 14 or CLM-14). If omitted, retrieves recent claims.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'pre_approve_claim',
                        'description' => 'Finance role action: Audit and pre-approve a pending claim, escalating it to executive management for final sign-off.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'STRING',
                                    'description' => 'The numeric ID of the claim to pre-approve (e.g., 14 or CLM-14).',
                                ],
                                'audit_notes' => [
                                    'type' => 'STRING',
                                    'description' => 'Audit remarks or verification notes from the Finance desk.',
                                ],
                            ],
                            'required' => ['claim_id'],
                        ],
                    ],
                    [
                        'name' => 'final_approve_claim',
                        'description' => 'Manager role action: Grant final executive authorization on a claim voucher for finance disbursement.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'STRING',
                                    'description' => 'The numeric ID of the claim to approve (e.g., 14 or CLM-14).',
                                ],
                                'approval_remarks' => [
                                    'type' => 'STRING',
                                    'description' => 'Executive approval comments or remarks.',
                                ],
                            ],
                            'required' => ['claim_id'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build contextual system instruction tailored to the authenticated user and role.
     */
    protected function buildSystemInstruction(?User $user): string
    {
        $userName = $user ? $user->name : 'Valued Employee';
        $userRole = $user ? strtolower(trim($user->role ?? 'staff')) : 'staff';

        $roleSpecificGuidance = match ($userRole) {
            'manager' => "- You are assisting an Executive Manager ({$userName}). They have authority to execute `final_approve_claim` and view all claims with `check_claim_status`. Prioritize concise financial summaries, risk scores, and approval actions.",
            'finance', 'fin' => "- You are assisting a Finance Officer ({$userName}). They have authority to audit and execute `pre_approve_claim` on pending claims and inspect all records with `check_claim_status`.",
            default => "- You are assisting a Staff Employee ({$userName}). They can submit claims via `submit_claim` and check their personal claim statuses with `check_claim_status`. When receipts are provided, extract merchant, date, amount, and category, and ask or submit on their behalf.",
        };

        return <<<TEXT
You are the SmartClaim AI Conversational Assistant, an intelligent expense management agent for SmartClaim.
Operating Context:
- Currency: Malaysian Ringgit (MYR / RM). Always format amounts like "RM 45.50".
- Current User: {$userName} (Role: {$userRole}).
{$roleSpecificGuidance}

Guidelines:
1. Always maintain a professional, helpful, and concise demeanor.
2. For multimodal receipt images: Extract the vendor/merchant name, transaction date (YYYY-MM-DD), total amount, and recommended category (Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive, Utilities).
3. If the user explicitly asks to submit a claim or asks you to file an analyzed receipt, invoke the `submit_claim` function immediately.
4. If a Finance officer asks to pre-approve or verify a claim voucher, invoke `pre_approve_claim`.
5. If a Manager asks to approve a claim voucher, invoke `final_approve_claim`.
6. If the user asks about the status of claims or a specific claim, invoke `check_claim_status`.
7. Never invent fictitious claim IDs. If an action fails with an error or access restriction, explain the policy rule clearly and politely to the user.
8. Answer all conversational questions in English.
TEXT;
    }

    /**
     * Clean and ensure history contents conform strictly to the Gemini API JSON schema.
     */
    protected function sanitizeHistoryForApi(array $history): array
    {
        $sanitized = [];

        foreach ($history as $turn) {
            $role = $turn['role'] ?? 'user';
            $parts = $turn['parts'] ?? [];
            $cleanParts = [];

            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $cleanParts[] = ['text' => (string) $part['text']];
                } elseif (isset($part['inlineData']) || isset($part['inline_data'])) {
                    $rawInline = $part['inlineData'] ?? $part['inline_data'];
                    if (!empty($rawInline['data']) && !empty($rawInline['mimeType'])) {
                        $cleanParts[] = [
                            'inlineData' => [
                                'mimeType' => $rawInline['mimeType'],
                                'data' => $rawInline['data'],
                            ],
                        ];
                    }
                } elseif (isset($part['functionCall']) || isset($part['function_call'])) {
                    $call = $part['functionCall'] ?? $part['function_call'];
                    $cleanParts[] = [
                        'functionCall' => [
                            'name' => $call['name'],
                            'args' => (object) ($call['args'] ?? []),
                        ],
                    ];
                } elseif (isset($part['functionResponse']) || isset($part['function_response'])) {
                    $resp = $part['functionResponse'] ?? $part['function_response'];
                    $cleanParts[] = [
                        'functionResponse' => [
                            'name' => $resp['name'],
                            'response' => [
                                'name' => $resp['name'],
                                'content' => $resp['response']['content'] ?? $resp['response'] ?? [],
                            ],
                        ],
                    ];
                }
            }

            if (!empty($cleanParts)) {
                $sanitized[] = [
                    'role' => $role,
                    'parts' => $cleanParts,
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Produce human-friendly status label for the action status chip badge.
     */
    protected function getActionLabel(string $toolName, array $result): string
    {
        $status = $result['status'] ?? 'completed';

        if ($status !== 'success') {
            return "Action Failed: " . ($result['message'] ?? 'Execution error');
        }

        return match ($toolName) {
            'submit_claim' => "Action Executed: Claim Submitted #" . ($result['voucher_no'] ?? $result['claim_id'] ?? ''),
            'check_claim_status' => "Action Executed: Claims Checked",
            'pre_approve_claim' => "Action Executed: Pre-Approved #" . ($result['voucher_no'] ?? $result['claim_id'] ?? ''),
            'final_approve_claim' => "Action Executed: Final Approved #" . ($result['voucher_no'] ?? $result['claim_id'] ?? ''),
            default => "Action Executed: {$toolName}",
        };
    }
}
