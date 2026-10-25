<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;
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
        // Early Out-of-Scope Interception (Token-saving <50ms response)
        if ($this->isExplicitlyOutOfScope($userMessage ?? '', !empty($receiptAttachment))) {
            $refusal = "Maaf, saya ialah SmartClaim Assistant yang dikhususkan untuk urusan tuntutan perbelanjaan, semakan resit, elaun perjalanan (mileage), dan audit syarikat sahaja. Saya tidak dapat menjawab topik di luar skop sistem ini. Ada sebarang urusan tuntutan yang boleh saya bantu?

⚡ Tindakan: Semak status tuntutan saya
⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan
⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)";

            return [
                'reply' => $refusal,
                'history' => $history,
                'action_executed' => null,
            ];
        }

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

        // Early Out-of-Scope Interception (Token-saving <50ms response)
        if ($this->isExplicitlyOutOfScope($userMessage ?? '', !empty($receiptAttachment))) {
            $refusal = "Maaf, saya ialah SmartClaim Assistant yang dikhususkan untuk urusan tuntutan perbelanjaan, semakan resit, elaun perjalanan (mileage), dan audit syarikat sahaja. Saya tidak dapat menjawab topik di luar skop sistem ini. Ada sebarang urusan tuntutan yang boleh saya bantu?\n\n⚡ Tindakan: Semak status tuntutan saya\n⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan\n⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)";

            return [
                'reply' => $refusal,
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
        if (!empty($this->fallbackModel) && \Illuminate\Support\Facades\Cache::has('gemini_quota_exhausted_' . $this->model)) {
            $activeModel = $this->fallbackModel;
        }
        $url = "{$this->endpoint}/{$activeModel}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $sanitizedHistory,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'tools' => $tools,
            'generationConfig' => $this->buildGenerationConfig($activeModel),
        ];

        try {
            $response = Http::timeout(45)
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
                if ($response->status() === 429) {
                    \Illuminate\Support\Facades\Cache::put('gemini_quota_exhausted_' . $activeModel, true, now()->addMinutes(15));
                }
                Log::warning("Gemini primary model '{$activeModel}' returned HTTP {$response->status()}. Attempting resilient fallback to '{$this->fallbackModel}'. Error: " . $response->body());
                $activeModel = $this->fallbackModel;
                $url = "{$this->endpoint}/{$activeModel}:generateContent?key={$this->apiKey}";
                $payload['generationConfig'] = $this->buildGenerationConfig($activeModel);
                $response = Http::timeout(45)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                    ])
                    ->post($url, $payload);

                if ($response->failed()) {
                    $this->logApiError('Gemini API Fallback Error', $response);
                }
            }

            if ($response->failed()) {
                $statusCode = $response->status();
                $this->logApiError('Gemini API Error', $response);

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
                    'generationConfig' => $this->buildGenerationConfig($activeModel),
                ];

                $secondResponse = Http::timeout(45)
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
                    $this->logApiError('Gemini Second Turn Error', $secondResponse);
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

            $isTimeout = $e instanceof ConnectionException
                || str_contains(strtolower($e->getMessage()), 'curl error 28')
                || str_contains(strtolower($e->getMessage()), 'timed out');

            $errorMessage = $isTimeout
                ? "The AI service took a little too long to respond while processing your request. Please try sending your message again."
                : "I encountered an error connecting to the Gemini AI service (" . $e->getMessage() . "). Please try again in a moment.";

            return [
                'reply' => $errorMessage,
                'history' => $history,
                'action_executed' => null,
            ];
        }
    }

    /**
     * Build generationConfig payload conditionally based on active model support.
     * Only gemini-3.8-flash and gemini-3.1-flash-lite strictly belong to Gemini 3 Flash models supporting thinkingBudget: 0.
     * Non-3 or models like gemini-3.5-flash-lite / gemini-2.5-flash reject thinkingConfig with HTTP 400.
     */
    /**
     * Check if a user message is blatantly outside the scope of expense claims,
     * receipt scanning, mileage allowance, and corporate operations.
     *
     * @param string $message
     * @param bool $hasImage
     * @return bool
     */
    public function isExplicitlyOutOfScope(string $message, bool $hasImage = false): bool
    {
        if ($hasImage) {
            return false; // Always process receipt images
        }

        $trimmed = trim($message);
        if ($trimmed === '') {
            return false;
        }

        // Never intercept messages containing core claim / expense terminology
        $claimKeywordsPattern = '/\b(claim|tuntut|tuntutan|resit|receipt|mileage|elaun|perjalanan|bajet|budget|petrol|fuel|refuel|minyak|kenderaan|vehicle|plate|kereta|car|motor|van|lulus|approve|approval|audit|semak|check|status|clm|voucher|disburse|reimburse|rm|biaya|expense|perbelanjaan)\b/i';
        if (preg_match($claimKeywordsPattern, $trimmed)) {
            return false;
        }

        // 1. Coding / Programming requests
        $codingPatterns = [
            '/\b(write|create|buat|tulis|generate|solve|kod|coding|code)\b.*\b(python|javascript|typescript|php|java|c\+\+|c#|ruby|golang|rust|leetcode|algorithm|function|script|regex|sql query|html|css)\b/i',
            '/\b(python|javascript|typescript|php|java|c\+\+|leetcode|algorithm)\b.*\b(code|function|solve|script|programming)\b/i',
            '/\b(write python code|buat function javascript|solve leetcode|debug this code|coding tutorial)\b/i',
        ];

        // 2. General recipes / cooking
        $cookingPatterns = [
            '/\b(resepi|recipe|cara masak|how to cook|resep|bahan masakan|cara buat kuih|resepi rendang|resepi kek|cara buat kek|menu masakan)\b/i',
        ];

        // 3. Trivia, politics, gaming, sports, crypto, creative writing
        $triviaPatterns = [
            '/\b(siapa presiden|who is the president|perdana menteri|prime minister|who won|siapa menang)\b/i',
            '/\b(crypto price|harga crypto|harga bitcoin|bitcoin price|ethereum|dogecoin|crypto market)\b/i',
            '/\b(lirik lagu|song lyrics|chord gitar|chord guitar)\b/i',
            '/\b(tulis puisi|tulis sajak|skrip drama|write a poem|write a story|cerita dongeng|teka-teki|cerita lawak|tell me a joke)\b/i',
            '/\b(skor bola|football score|premier league|jadual perlawanan|main game|cheat code)\b/i',
        ];

        $allOutOfScopePatterns = array_merge($codingPatterns, $cookingPatterns, $triviaPatterns);

        foreach ($allOutOfScopePatterns as $pattern) {
            if (preg_match($pattern, $trimmed)) {
                return true;
            }
        }

        return false;
    }

    public function buildGenerationConfig(string $model): array
    {
        $config = [
            'temperature' => 0.1,
            'maxOutputTokens' => 1024,
        ];

        $supportedThinkingModels = ['gemini-3.8-flash', 'gemini-3.1-flash-lite'];
        if (in_array(strtolower(trim($model)), $supportedThinkingModels, true)) {
            $config['thinkingConfig'] = [
                'thinkingBudget' => 0,
            ];
        }

        return $config;
    }

    /**
     * Log API errors with enhanced field-level diagnostics for HTTP 400 Bad Request responses.
     */
    protected function logApiError(string $context, \Illuminate\Http\Client\Response $response): void
    {
        $statusCode = $response->status();
        $rawBody = $response->body();
        $errorData = $response->json();

        if ($statusCode === 400 && !empty($errorData['error'])) {
            $failedFields = [];
            $details = $errorData['error']['details'] ?? [];

            if (is_array($details)) {
                foreach ($details as $detail) {
                    if (!empty($detail['fieldViolations']) && is_array($detail['fieldViolations'])) {
                        foreach ($detail['fieldViolations'] as $violation) {
                            $field = $violation['field'] ?? 'unknown_field';
                            $desc = $violation['description'] ?? 'invalid argument';
                            $failedFields[] = "{$field} ({$desc})";
                        }
                    } elseif (!empty($detail['description'])) {
                        $failedFields[] = $detail['description'];
                    }
                }
            }

            $errorMessage = $errorData['error']['message'] ?? 'Invalid argument';
            if (empty($failedFields)) {
                $failedFields[] = $errorMessage;
            }

            $failedFieldsStr = implode(', ', $failedFields);
            Log::error("{$context} [HTTP 400 Invalid Argument] - Field(s) failed: [{$failedFieldsStr}]. Response: {$rawBody}");
        } else {
            Log::error("{$context} [{$statusCode}]: {$rawBody}");
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
                                'vehicle_plate' => [
                                    'type' => 'string',
                                    'description' => 'Vehicle registration plate number for Fuel / Automotive / Mileage expenses (e.g., "JKE 1234" or "VCE 2024"). Required for fuel and fleet claims.',
                                ],
                                'vehicle_id' => [
                                    'type' => 'number',
                                    'description' => 'Optional numeric ID of the registered vehicle.',
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

        $now = now()->timezone('Asia/Kuala_Lumpur');
        $currentDateMalay = $now->locale('ms')->translatedFormat('l, d F Y');
        $currentDateEn = $now->format('l, F j, Y');
        $currentTime = $now->format('h:i A');

        $personalVehicles = ($user && method_exists($user, 'vehicles'))
            ? $user->vehicles()->personal()->get()
            : collect();

        if ($personalVehicles->isNotEmpty()) {
            $personalVehiclesSummary = $personalVehicles->map(function ($v) {
                return "- Personal Vehicle: {$v->plate_number} ({$v->brand_model}, Status: {$v->approval_status})";
            })->implode("\n");
        } else {
            $personalVehiclesSummary = "- No registered personal vehicles found for this staff.";
        }

        $companyFleet = \App\Models\Vehicle::companyFleet()->get();
        if ($companyFleet->isNotEmpty()) {
            $companyFleetSummary = $companyFleet->map(function ($v) {
                return "- Company Fleet Vehicle: {$v->plate_number} ({$v->brand_model})";
            })->implode("\n");
        } else {
            $companyFleetSummary = "- No company fleet vehicles currently active.";
        }

        $roleSpecificGuidance = match ($userRole) {
            'manager' => "- Assisting Executive Manager ({$userName}). They have authority to execute `final_approve_claim` and view all claims with `check_claim_status`. Prioritize financial summaries and approval actions.",
            'finance', 'fin' => "- Assisting Finance Officer ({$userName}). They have authority to execute `pre_approve_claim` on pending claims and inspect all records with `check_claim_status`.",
            default => "- Assisting Staff Employee ({$userName}). They can submit claims via `submit_claim` and check personal claim statuses with `check_claim_status`. Guide them through claims following the universal claim segregation.",
        };

        return <<<TEXT
Temporal Operating Context (LIVE SERVER CLOCK):
- Current System Time: {$currentTime} (Asia/Kuala_Lumpur)
- Current Date & Day (Bahasa Melayu): {$currentDateMalay}
- Current Date & Day (English): {$currentDateEn}
- Use this exact timeline to answer relative date questions (e.g. "hari ini", "esok", "semalam", "hari apa") and to audit receipt backdating compliance accurately. NEVER guess the current day or date.

Operating Context:
- System: SmartClaim (Automated Expense Claim Management System).
- Currency: Malaysian Ringgit (MYR / RM). Always format amounts like "RM 45.50".
- Current User: {$userName} (Role: {$userRole}).
{$roleSpecificGuidance}

Registered Vehicles Registry:
- Staff Personal Vehicles (Strictly for Mileage Allowance Claims):
{$personalVehiclesSummary}

- Company Fleet Vehicles (Strictly for Fuel & Fleet Logistics Claims):
{$companyFleetSummary}

Language Policy (STRICT):
- Strictly communicate ONLY in standard Bahasa Melayu (BM) or English. Match whichever language the user initiates with.
- ABSOLUTELY FORBIDDEN: NEVER use Bahasa Indonesia. Do NOT use Indonesian vocabulary or phrasing (e.g. NEVER say "Tentu, saya bisa membantu Anda...", "Toko", "Tanggal", "Bisa", "Anda", "Struk", "Unggah", "Kembali", "Gimana"). In Bahasa Melayu, use standard terms: "Tentu, saya boleh bantu", "Kedai/Peniaga", "Tarikh", "Boleh", "Resit", "Muat naik", "Awak/Tuan/Puan".

Conversational Scope & 3-Zone Guardrails:

Zone 1 (Core In-Scope):
- Expense claims, receipts, corporate fleet fuel, mileage allowance, status lookups, policy limits, and financial audits.
- Process normally using function calling, structured extraction, and claim workflows.

Zone 2 (Office Small Talk & Temporal Context):
- Greetings ("hai", "hello", "selamat pagi"), polite remarks ("terima kasih", "sama-sama"), and date/calendar questions ("esok hari apa", "tarikh hari ini", "semalam hari apa").
- Answer directly and concisely in 1 sentence using the Live Server Temporal Context, then smoothly bridge back to expense claims.
- Example: If asked "esok hari apa", calculate based on the server clock (e.g., "Hari ini hari Ahad, jadi esok ialah hari Isnin, 26 Oktober 2026. Ada sebarang resit atau tuntutan yang ingin anda kemukakan?") and output action chips.

Zone 3 (Out-of-Scope Rejection):
- For queries outside expense claims, receipt management, mileage allowance, and office greetings that slipped past backend regex (e.g. general recipes, coding help, trivia, creative writing):
- Politely decline, clarify role as SmartClaim Assistant, and redirect with claim action chips:
  In BM: "Maaf, saya ialah SmartClaim Assistant yang dikhususkan untuk urusan tuntutan perbelanjaan, semakan resit, elaun perjalanan (mileage), dan audit syarikat sahaja. Saya tidak dapat menjawab topik di luar skop sistem ini. Ada sebarang urusan tuntutan yang boleh saya bantu?"
  In English: "I apologize, but I am the SmartClaim Assistant dedicated exclusively to corporate expense claims, receipt scanning, mileage allowance, and company audits. I cannot assist with topics outside this scope. Is there any expense claim I can assist you with?"
  Include Action Chips:
  ⚡ Tindakan: Semak status tuntutan saya
  ⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan
  ⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)

Claim Categorization & Conversational Workflows (STRICT):

A. Text-Only Claim Inquiries (No image uploaded):
- If the user asks generally to submit, create, or start a claim (e.g., "Saya nak buat claim", "How to submit claim", "I want to claim expense", "Bantu saya buat tuntutan"):
  - Present the two core claim options clearly:
    In Bahasa Melayu:
    "Sila pilih jenis tuntutan yang ingin anda lakukan:
    1. **Tuntutan Resit Perbelanjaan** — Tuntutan bayaran balik berasaskan resit (cth: petrol armada syarikat, makanan operasi, tol/parkir, alatan tapak, atau hotel).
    2. **Tuntutan Elaun Perjalanan (Mileage)** — Pengiraan tuntutan jarak (KM) bagi perjalanan tugas rasmi menggunakan kenderaan peribadi."
    Output Action Chips:
    ⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan
    ⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)

    In English:
    "Please select the type of claim you would like to initiate:
    1. **Expense Receipt Claim** — Reimbursement based on receipts (e.g. corporate fleet fuel, operational meals, toll/parking, site tools, or hotel/travel).
    2. **Mileage Allowance Claim** — Per-KM distance allowance for official corporate travel using your personal registered vehicle."
    Output Action Chips:
    ⚡ Action: I want to submit an expense receipt claim
    ⚡ Action: I want to claim mileage allowance

B. Receipt Uploaded - General Non-Fuel Claims:
- When a receipt image is provided and belongs to non-fuel categories (e.g., Staff Operational Meals, Office Pantry, Office Supplies, Site Tools, Travel & Lodging, Transportation & Toll):
  - Extract: Merchant, Date, Amount, Category, Purpose.
  - DO NOT prompt for any vehicle details.
  - Output standard structured review summary:
Merchant: [Extracted Merchant Name]
Date: [YYYY-MM-DD]
Amount: RM [0.00]
Category: [Recommended Category]
Purpose: [Suggested Business Purpose]
  - Ask the user to verify these details before submission.

C. Receipt Uploaded - Fuel & Fleet Logistics (Corporate Fleet / Petrol):
- When a receipt image is provided and categorized as 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)':
  - Emphasize that receipt fuel claims are for company operational fleet vehicles.
  - Extract:
Merchant: [Extracted Merchant Name]
Date: [YYYY-MM-DD]
Amount: RM [0.00]
Category: Fuel & Fleet Logistics (Corporate Fleet / Petrol)
Purpose: [Suggested Business Purpose]
Vehicle Plate: [Selected Company Fleet Plate or Unspecified]
  - If no vehicle plate was detected in the receipt or user message:
    - Set Vehicle Plate to Unspecified.
    - Ask the user to select which company fleet vehicle was used for this refuel (or specify if their personal car was used).
    - Output Action Chips for available company fleet plates (e.g. "⚡ Tindakan: Guna kenderaan syarikat WA 8888 A", "⚡ Tindakan: Guna kenderaan syarikat BKA 8821" or in English "⚡ Action: I used company fleet vehicle WA 8888 A").
  - Once the user confirms the vehicle plate, include `vehicle_plate` when calling `submit_claim`.

D. Mileage Allowance Claims:
- When the user selects "Saya mahu buat tuntutan elaun perjalanan (mileage)" or "I want to claim mileage allowance":
  - Explain that mileage claims do not require receipt images, as they are calculated based on official travel distance and approved personal vehicles.
  - Prompt them to provide travel details:
    1. Origin (Lokasi Berlepas)
    2. Destination (Lokasi Destinasi)
    3. Estimated Distance in KM (Anggaran Jarak KM)
    4. Registered Personal Vehicle (Kenderaan Peribadi) — list their registered personal plate(s) (e.g. {$personalVehiclesSummary}).

E. Authorization & Approvals:
- If a Finance officer requests to audit or pre-approve a claim, invoke `pre_approve_claim`.
- If a Manager requests to approve a claim, invoke `final_approve_claim`.
- If the user asks about claim status, invoke `check_claim_status`.
- Never fabricate claim IDs. Explain any policy constraints cleanly.

Safe Edit-Review Confirmation Cycle:
- When a user provides updated or edited claim details (e.g. starting with 'Here are my updated claim details for review:' or providing modified fields):
  1. NEVER immediately invoke `submit_claim`.
  2. Output the updated structured review summary with all details.
  3. Ask the user for their final confirmation (e.g. 'Please confirm if these updated details look correct, or click Proceed & Submit Claim.').
  4. Only call `submit_claim` when the user explicitly clicks 'Proceed & Submit Claim' or says 'Confirm and submit' or 'Yes, proceed'.

Claim List Layout & Presentation (STRICT):
- STRICT PROHIBITION ON PIPE TABLES: NEVER format claim records or queries using ASCII/Markdown pipe tables (| Column | Column |). The chat drawer is too narrow for tabular layouts.
- UNIFIED BULLET LIST FORMAT: Whenever displaying a list of claims (for Staff, Finance, or Manager), ALWAYS output them as a clean bulleted list using this uniform structure:
  * **CLM-[ID]**: RM [Amount] at [Merchant] ([Date]) — **[Status]** [Include Applicant Name if Finance/Manager]
  Example:
  * **CLM-298374**: RM 90.00 at Optima DNA Enterprise (2026-03-29) — **Pending** (Ahmad Faiz)

Follow-up Questions & User Action Chips:
- At the end of your response, you may naturally ask 1 helpful follow-up question directly in your text (e.g., "Would you like me to guide you through submitting a new receipt?" or "Adakah anda ingin saya semak baki had bajet bulanan anda?").
- Below your text response, output 1 or 2 clickable USER ACTIONS prefixed with "⚡ Action: " (or "⚡ Tindakan: " in BM).
- STRICT RULES FOR USER ACTIONS:
  1. Actions MUST be imperative user commands or first-person statements (what the USER tells the AI when tapped).
  2. NEVER write questions or second-person phrases like "Do you have...", "Would you like to...", "Adakah anda...", "Perlukan bantuan...".
  3. Examples of VALID Actions:
     - In English:
       ⚡ Action: I want to submit a new receipt claim
       ⚡ Action: Check my monthly budget balance
       ⚡ Action: Check status of claim #CLM-3
     - In Bahasa Melayu:
       ⚡ Tindakan: Saya mahu hantar tuntutan resit baharu
       ⚡ Tindakan: Semak baki had bajet bulanan saya
       ⚡ Tindakan: Semak butiran tuntutan #CLM-3
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
            'submit_claim' => ($voucher ? "{$voucher} " : "") . "submitted for audit" . (!empty($result['vehicle_plate']) ? " ({$result['vehicle_plate']})" : ""),
            'check_claim_status' => "Claim status verified",
            'pre_approve_claim' => ($voucher ? "{$voucher} " : "") . "pre-approved by Finance",
            'final_approve_claim' => ($voucher ? "{$voucher} " : "") . "approved by Management",
            default => "Action executed: {$toolName}",
        };
    }
}
