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
    protected string $fallbackModel;
    protected string $endpoint;
    protected ChatbotActionService $actionService;

    public function __construct(ChatbotActionService $actionService)
    {
        $this->actionService = $actionService;
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-3.8-flash');
        $this->fallbackModel = config('services.gemini.fallback_model', 'gemini-3.1-flash-lite');
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
                'text' => 'Please analyze this uploaded receipt image. Extract the merchant name, transaction date (YYYY-MM-DD), total amount (in RM / MYR), and recommended expense category (Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive, Utilities). Summarize the findings concisely and ask if I would like to submit this claim.',
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

        // Format history for Gemini API: remove non-standard client metadata
        $sanitizedHistory = $this->sanitizeHistoryForApi($history);

        $systemInstruction = $this->buildSystemInstruction($user);
        $tools = $this->getToolsDeclaration();

        $activeModel = $this->model;
        $url = "{$this->endpoint}/{$activeModel}:generateContent?key={$this->apiKey}";

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
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                ])
                ->post($url, $payload);

            // Resilient Fallback: If primary model hits 429 quota or 503 high-demand, attempt fallback model
            if ($response->failed() && in_array($response->status(), [429, 503]) && !empty($this->fallbackModel) && $this->fallbackModel !== $activeModel) {
                Log::warning("Gemini primary model '{$activeModel}' returned HTTP {$response->status()}. Attempting resilient fallback to '{$this->fallbackModel}'. Error: " . $response->body());
                $activeModel = $this->fallbackModel;
                $url = "{$this->endpoint}/{$activeModel}:generateContent?key={$this->apiKey}";
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                    ])
                    ->post($url, $payload);
            }

            if ($response->failed()) {
                $statusCode = $response->status();
                $rawBody = $response->body();
                Log::error("Gemini API Error [{$statusCode}]: {$rawBody}");

                $errorData = $response->json();
                $apiErrorMsg = $errorData['error']['message'] ?? null;

                $userFriendlyReply = match ($statusCode) {
                    400 => "Request format issue with Gemini AI: " . ($apiErrorMsg ?? 'Invalid schema parameters or bad request.'),
                    403 => "Gemini API authorization failed. Please verify your GEMINI_API_KEY configuration.",
                    404 => "The configured model '{$this->model}' was not found. " . ($apiErrorMsg ?? 'Please check GEMINI_MODEL in .env.'),
                    429 => "Gemini API rate limit reached. Please wait a moment before trying again.",
                    503 => "The Gemini AI service is temporarily experiencing high traffic. Please try again in a moment.",
                    default => "Unable to communicate with Gemini AI (" . ($apiErrorMsg ?? "HTTP {$statusCode}") . "). Please try again later.",
                };

                return [
                    'reply' => $userFriendlyReply,
                    'history' => $history,
                    'action_executed' => null,
                ];
            }

            $responseData = $response->json();
            $candidate = $responseData['candidates'][0] ?? null;

            if (!$candidate || empty($candidate['content']['parts'])) {
                Log::warning('Gemini API returned empty candidate content: ' . json_encode($responseData));
                return [
                    'reply' => "I did not receive a readable response from Gemini. Please try again.",
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

            // If Gemini executed a Function Call tool invocation
            if ($functionCallPart) {
                $toolName = $functionCallPart['name'];
                $toolArgs = $functionCallPart['args'] ?? [];

                // Delegate execution to ChatbotActionService with strict RBAC
                $activeReceipt = $receiptAttachment ?? $sessionReceipt;
                $actionResult = $this->actionService->executeAction($toolName, $toolArgs, $activeReceipt);

                // Append model's tool call turn to history (preserving thoughts and thoughtSignature for Gemini 3)
                $normalizedModelParts = [];
                foreach ($candidate['content']['parts'] as $candidatePart) {
                    if (isset($candidatePart['functionCall']) && is_array($candidatePart['functionCall']['args'] ?? null) && empty($candidatePart['functionCall']['args'])) {
                        $candidatePart['functionCall']['args'] = (object) [];
                    }
                    $normalizedModelParts[] = $candidatePart;
                }

                $modelTurn = [
                    'role' => 'model',
                    'parts' => $normalizedModelParts,
                ];
                $history[] = $modelTurn;
                $sanitizedHistory[] = $modelTurn;

                // Append user's tool response turn to history
                $functionResponsePayload = [
                    'name' => $toolName,
                    'response' => [
                        'name' => $toolName,
                        'content' => $actionResult,
                    ],
                ];
                if (!empty($functionCallPart['id'])) {
                    $functionResponsePayload['id'] = $functionCallPart['id'];
                }

                $functionResponseTurn = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => $functionResponsePayload,
                        ],
                    ],
                ];
                $history[] = $functionResponseTurn;
                $sanitizedHistory[] = $functionResponseTurn;

                // Call Gemini again with function output for final conversational response
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
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                    ])
                    ->post($url, $secondPayload);

                $conversationalReply = "I have processed the requested action: {$toolName}.";

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
                } else {
                    Log::error("Gemini Second Turn Error [{$secondResponse->status()}]: " . $secondResponse->body());
                    if (!empty($actionResult['message'])) {
                        $conversationalReply = $actionResult['message'];
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
                    'status' => $actionResult['status'] ?? 'completed',
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
                : "I processed your request, but received no text output.";

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
                'reply' => "I encountered an error connecting to the Gemini AI service (" . $e->getMessage() . "). Please try again in a moment.",
                'history' => $history,
                'action_executed' => null,
            ];
        }
    }

    /**
     * Provide function calling tool declarations strictly adhering to standard OpenAPI types in lowercase.
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
                            'type' => 'object',
                            'properties' => [
                                'category' => [
                                    'type' => 'string',
                                    'description' => 'Expense category (e.g., Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive, Utilities).',
                                ],
                                'amount' => [
                                    'type' => 'number',
                                    'description' => 'The numeric total amount of the claim in Malaysian Ringgit (MYR / RM).',
                                ],
                                'merchant_name' => [
                                    'type' => 'string',
                                    'description' => 'The merchant, vendor, or store name where the transaction occurred.',
                                ],
                                'receipt_date' => [
                                    'type' => 'string',
                                    'description' => 'The transaction date formatted as YYYY-MM-DD.',
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'Business purpose or justification for the expenditure.',
                                ],
                            ],
                            'required' => ['category', 'amount', 'merchant_name', 'description'],
                        ],
                    ],
                    [
                        'name' => 'check_claim_status',
                        'description' => 'Check the status and details of expense claims. Staff can check their own submissions. Finance and Managers can inspect any claim by ID or view recent submissions.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'string',
                                    'description' => 'Optional numeric ID of the claim voucher (e.g., 14 or CLM-14). If omitted, retrieves recent claims.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'pre_approve_claim',
                        'description' => 'Finance role action: Audit and pre-approve a pending claim, escalating it to executive management for final sign-off.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'string',
                                    'description' => 'The numeric ID of the claim to pre-approve (e.g., 14 or CLM-14).',
                                ],
                                'audit_notes' => [
                                    'type' => 'string',
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
                            'type' => 'object',
                            'properties' => [
                                'claim_id' => [
                                    'type' => 'string',
                                    'description' => 'The numeric ID of the claim to approve (e.g., 14 or CLM-14).',
                                ],
                                'approval_remarks' => [
                                    'type' => 'string',
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
            'manager' => "- Assisting Executive Manager ({$userName}). They have authority to execute `final_approve_claim` and view all claims with `check_claim_status`. Prioritize financial summaries and approval actions.",
            'finance', 'fin' => "- Assisting Finance Officer ({$userName}). They have authority to execute `pre_approve_claim` on pending claims and inspect all records with `check_claim_status`.",
            default => "- Assisting Staff Employee ({$userName}). They can submit claims via `submit_claim` and check personal claim statuses with `check_claim_status`. When receipts are provided, extract merchant, date, amount, and category, then ask or submit on their behalf.",
        };

        return <<<TEXT
You are the SmartClaim Assistant, an enterprise-grade AI expense assistant.
Operating Context:
- Currency: Malaysian Ringgit (MYR / RM). Always format amounts like "RM 45.50".
- Current User: {$userName} (Role: {$userRole}).
{$roleSpecificGuidance}

Instructions:
1. Provide concise, clear, and professional responses in English.
2. For multimodal receipt images: Extract vendor name, transaction date (YYYY-MM-DD), total amount, and recommended category (Meals & Entertainment, Office Supplies, Travel, Accommodations, Fuel / Automotive, Utilities).
3. If requested to submit a claim, invoke `submit_claim`.
4. If a Finance officer requests to audit or pre-approve a claim, invoke `pre_approve_claim`.
5. If a Manager requests to approve a claim, invoke `final_approve_claim`.
6. If the user asks about claim status, invoke `check_claim_status`.
7. Never fabricate claim IDs. Explain any policy constraints cleanly.
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
                    $cleanCallPart = [
                        'functionCall' => [
                            'name' => $call['name'],
                            'args' => (object) ($call['args'] ?? []),
                        ],
                    ];
                    if (!empty($call['id'])) {
                        $cleanCallPart['functionCall']['id'] = $call['id'];
                    }
                    if (!empty($part['thoughtSignature'])) {
                        $cleanCallPart['thoughtSignature'] = $part['thoughtSignature'];
                    } elseif (!empty($part['thought_signature'])) {
                        $cleanCallPart['thoughtSignature'] = $part['thought_signature'];
                    }
                    $cleanParts[] = $cleanCallPart;
                } elseif (isset($part['functionResponse']) || isset($part['function_response'])) {
                    $resp = $part['functionResponse'] ?? $part['function_response'];
                    $cleanResp = [
                        'name' => $resp['name'],
                        'response' => [
                            'name' => $resp['name'],
                            'content' => $resp['response']['content'] ?? $resp['response'] ?? [],
                        ],
                    ];
                    if (!empty($resp['id'])) {
                        $cleanResp['id'] = $resp['id'];
                    }
                    $cleanParts[] = [
                        'functionResponse' => $cleanResp,
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
     * Produce clean, minimalist status label for action badge pill.
     */
    protected function getActionLabel(string $toolName, array $result): string
    {
        $status = $result['status'] ?? 'completed';

        if ($status !== 'success') {
            return "Failed: " . ($result['message'] ?? 'Execution error');
        }

        $voucher = $result['voucher_no'] ?? (!empty($result['claim_id']) ? "CLM-{$result['claim_id']}" : '');

        return match ($toolName) {
            'submit_claim' => ($voucher ? "{$voucher} " : "") . "submitted for audit",
            'check_claim_status' => "Claim status verified",
            'pre_approve_claim' => ($voucher ? "{$voucher} " : "") . "pre-approved by Finance",
            'final_approve_claim' => ($voucher ? "{$voucher} " : "") . "approved by Management",
            default => "Action executed: {$toolName}",
        };
    }
}
