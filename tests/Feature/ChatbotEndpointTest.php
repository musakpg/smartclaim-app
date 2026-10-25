<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\ChatbotActionService;
use App\Services\GeminiChatbotService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_blocked_from_chatbot_endpoints(): void
    {
        $messageResponse = $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
        ]);
        $messageResponse->assertStatus(401);

        $historyResponse = $this->getJson(route('chatbot.history'));
        $historyResponse->assertStatus(401);

        $resetResponse = $this->postJson(route('chatbot.reset'));
        $resetResponse->assertStatus(401);
    }

    public function test_authenticated_staff_receives_successful_chatbot_response(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $mockService = $this->createMock(GeminiChatbotService::class);
        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn([
                'reply' => 'Here are your pending claims.',
                'history' => [],
                'action_executed' => [
                    'tool' => 'check_claim_status',
                    'status' => 'success',
                    'label' => 'Claim status verified',
                    'data' => [],
                ],
            ]);

        $this->app->instance(GeminiChatbotService::class, $mockService);

        $response = $this->actingAs($user)->postJson(route('chatbot.message'), [
            'message' => 'Check my claims',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'reply' => 'Here are your pending claims.',
            'action_executed' => [
                'tool' => 'check_claim_status',
                'status' => 'success',
            ],
        ]);
    }

    public function test_authenticated_user_can_reset_chatbot_session(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $response = $this->actingAs($user)->postJson(route('chatbot.reset'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Chatbot conversation history reset successfully.',
        ]);
    }

    public function test_chatbot_controller_catches_exceptions_gracefully_returning_clean_json_error(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $mockService = $this->createMock(GeminiChatbotService::class);
        $mockService->expects($this->once())
            ->method('chat')
            ->willThrowException(new Exception('Gemini API timeout simulation'));

        $this->app->instance(GeminiChatbotService::class, $mockService);

        $response = $this->actingAs($user)->postJson(route('chatbot.message'), [
            'message' => 'Check my claims',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Gemini API timeout simulation',
        ]);
    }

    public function test_chatbot_controller_catches_timeout_connection_exception_gracefully(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $mockService = $this->createMock(GeminiChatbotService::class);
        $mockService->expects($this->once())
            ->method('chat')
            ->willThrowException(new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out'));

        $this->app->instance(GeminiChatbotService::class, $mockService);

        $response = $this->actingAs($user)->postJson(route('chatbot.message'), [
            'message' => 'Check my claims',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'message' => 'The AI service took a little too long to respond while processing your request. Please try sending your message again.',
        ]);

        $historyResponse = $this->actingAs($user)->getJson(route('chatbot.history'));
        $historyResponse->assertStatus(200);
        $historyResponse->assertJsonFragment([
            'sender' => 'bot',
            'text' => 'The AI service took a little too long to respond while processing your request. Please try sending your message again.',
            'is_error' => true,
        ]);
    }

    public function test_gemini_chatbot_service_returns_helpful_message_on_timeout(): void
    {
        config(['services.gemini.api_key' => 'test-api-key']);

        \Illuminate\Support\Facades\Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 45000 milliseconds');
            },
        ]);

        $service = app(GeminiChatbotService::class);
        $result = $service->chat([], 'Hello');

        $this->assertEquals(
            'The AI service took a little too long to respond while processing your request. Please try sending your message again.',
            $result['reply']
        );
    }
    public function test_authenticated_user_can_retrieve_chat_history_and_persist_messages(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $mockService = $this->createMock(GeminiChatbotService::class);
        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn([
                'reply' => 'I have retrieved your monthly budget balance.',
                'history' => [],
                'action_executed' => null,
            ]);

        $this->app->instance(GeminiChatbotService::class, $mockService);

        // 1. Initial history is empty
        $resInitial = $this->actingAs($user)->getJson(route('chatbot.history'));
        $resInitial->assertStatus(200);
        $resInitial->assertJson([
            'status' => 'success',
            'messages' => [],
        ]);

        // 2. Send a message
        $this->actingAs($user)->postJson(route('chatbot.message'), [
            'message' => 'Check my monthly budget balance',
        ]);

        // 3. History now has persisted UI turns
        $resAfter = $this->actingAs($user)->getJson(route('chatbot.history'));
        $resAfter->assertStatus(200);
        $resAfter->assertJsonCount(2, 'messages');
        $resAfter->assertJsonPath('messages.0.sender', 'user');
        $resAfter->assertJsonPath('messages.0.text', 'Check my monthly budget balance');
        $resAfter->assertJsonPath('messages.1.sender', 'bot');
        $resAfter->assertJsonPath('messages.1.text', 'I have retrieved your monthly budget balance.');

        // 4. Reset clears the session messages
        $this->actingAs($user)->postJson(route('chatbot.reset'));
        $resReset = $this->actingAs($user)->getJson(route('chatbot.history'));
        $resReset->assertStatus(200);
        $resReset->assertJson([
            'status' => 'success',
            'messages' => [],
        ]);
    }

    public function test_fuel_claim_requires_vehicle_compliance(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $vehicle = Vehicle::create([
            'user_id' => $user->user_id,
            'plate_number' => 'WXY 9999',
            'brand_model' => 'Proton X70',
            'vehicle_type' => 'Car',
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $actionService = app(ChatbotActionService::class);

        // 1. Submitting fuel claim without vehicle plate should trigger compliance rejection
        $this->actingAs($user);
        $resultFail = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel / Automotive',
            'amount' => 85.00,
            'merchant_name' => 'Petronas Subang',
            'description' => 'Petrol refuel',
        ]);

        $this->assertEquals('error', $resultFail['status']);
        $this->assertStringContainsString('Vehicle Compliance Required', $resultFail['message']);
        $this->assertStringContainsString('WXY 9999', $resultFail['message']);

        // 2. Submitting with vehicle plate succeeds and links vehicle
        $resultSuccess = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel / Automotive',
            'amount' => 85.00,
            'merchant_name' => 'Petronas Subang',
            'description' => 'Petrol refuel',
            'vehicle_plate' => 'WXY 9999',
        ]);

        $this->assertEquals('success', $resultSuccess['status']);
        $this->assertEquals('WXY 9999', $resultSuccess['vehicle_plate']);
        $this->assertEquals($vehicle->vehicle_id, $resultSuccess['vehicle_id']);
    }

    public function test_thinking_config_is_conditionally_included_for_supported_models_only(): void
    {
        $service = app(GeminiChatbotService::class);

        // Models that support thinkingConfig
        $cfg38 = $service->buildGenerationConfig('gemini-3.8-flash');
        $this->assertArrayHasKey('thinkingConfig', $cfg38);
        $this->assertEquals(0, $cfg38['thinkingConfig']['thinkingBudget']);

        $cfg31 = $service->buildGenerationConfig('gemini-3.1-flash-lite');
        $this->assertArrayHasKey('thinkingConfig', $cfg31);
        $this->assertEquals(0, $cfg31['thinkingConfig']['thinkingBudget']);

        // Models that DO NOT support thinkingConfig and reject with HTTP 400
        $cfg35 = $service->buildGenerationConfig('gemini-3.5-flash-lite');
        $this->assertArrayNotHasKey('thinkingConfig', $cfg35);

        $cfg25 = $service->buildGenerationConfig('gemini-2.5-flash');
        $this->assertArrayNotHasKey('thinkingConfig', $cfg25);
    }

    public function test_tool_declarations_use_valid_openapi_types_and_no_integer(): void
    {
        $service = app(GeminiChatbotService::class);
        $tools = $service->getToolsDeclaration();

        $this->assertNotEmpty($tools);
        $validOpenApiTypes = ['string', 'number', 'object', 'boolean', 'array'];

        foreach ($tools as $toolGroup) {
            foreach ($toolGroup['functionDeclarations'] as $func) {
                if (isset($func['parameters']['properties'])) {
                    foreach ($func['parameters']['properties'] as $propName => $propDef) {
                        $this->assertArrayHasKey('type', $propDef);
                        $this->assertContains($propDef['type'], $validOpenApiTypes, "Property {$propName} in {$func['name']} has invalid type: {$propDef['type']}");
                        $this->assertNotEquals('integer', $propDef['type'], "Property {$propName} should use 'number', not 'integer'");
                    }
                }
            }
        }

        // Verify submit_claim specifics
        $submitClaim = collect($tools[0]['functionDeclarations'])->firstWhere('name', 'submit_claim');
        $this->assertNotNull($submitClaim);
        $this->assertEquals('number', $submitClaim['parameters']['properties']['vehicle_id']['type']);
        $this->assertEquals(
            ['category', 'amount', 'merchant_name', 'description'],
            $submitClaim['parameters']['required']
        );
    }

    public function test_gemini_chatbot_logs_detailed_field_errors_on_http_400(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'Request contains an invalid argument.',
                    'status' => 'INVALID_ARGUMENT',
                    'details' => [
                        [
                            '@type' => 'type.googleapis.com/google.rpc.BadRequest',
                            'fieldViolations' => [
                                [
                                    'field' => 'generation_config.thinking_config',
                                    'description' => 'thinking_budget is not supported for this model',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 400),
        ]);

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'generation_config.thinking_config')
                    && str_contains($message, 'HTTP 400 Invalid Argument');
            });

        $service = app(GeminiChatbotService::class);
        $result = $service->chat([], 'Hello');

        $this->assertStringContainsString('Request format issue with Gemini AI', $result['reply']);
    }

    public function test_vehicle_scopes_properly_filter_company_fleet_and_personal_vehicles(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);

        $companyActive = Vehicle::create([
            'plate_number' => 'FLEET-01',
            'brand_model' => 'Toyota Hilux Fleet',
            'vehicle_type' => 'Car',
            'ownership_type' => 'company',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $companyInactive = Vehicle::create([
            'plate_number' => 'FLEET-02',
            'brand_model' => 'Toyota Hiace Van',
            'vehicle_type' => 'Car',
            'ownership_type' => 'company',
            'status' => 'Inactive',
            'approval_status' => 'Approved',
        ]);

        $personalActive = Vehicle::create([
            'user_id' => $user->user_id,
            'plate_number' => 'PERS-01',
            'brand_model' => 'Perodua Myvi',
            'vehicle_type' => 'Car',
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $companyFleet = Vehicle::companyFleet()->get();
        $this->assertTrue($companyFleet->contains('plate_number', 'FLEET-01'));
        $this->assertFalse($companyFleet->contains('plate_number', 'FLEET-02'));
        $this->assertFalse($companyFleet->contains('plate_number', 'PERS-01'));

        $personal = Vehicle::personal()->get();
        $this->assertTrue($personal->contains('plate_number', 'PERS-01'));
        $this->assertFalse($personal->contains('plate_number', 'FLEET-01'));
    }

    public function test_fuel_claim_with_company_fleet_enforcement_and_matching(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $companyVehicle = Vehicle::create([
            'plate_number' => 'BKA 8821',
            'brand_model' => 'Toyota Hiace Van',
            'vehicle_type' => 'Car',
            'ownership_type' => 'company',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $personalVehicle = Vehicle::create([
            'user_id' => $user->user_id,
            'plate_number' => 'JKE 1234',
            'brand_model' => 'Perodua Myvi',
            'vehicle_type' => 'Car',
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $actionService = app(ChatbotActionService::class);
        $this->actingAs($user);

        // 1. Missing plate with active company fleet returns compliance error listing company fleet
        $failResult = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'amount' => 95.00,
            'merchant_name' => 'Petronas Senai',
            'description' => 'Fleet refuel',
        ]);

        $this->assertEquals('error', $failResult['status']);
        $this->assertStringContainsString('Vehicle Compliance Required', $failResult['message']);
        $this->assertStringContainsString('company fleet vehicle', $failResult['message']);
        $this->assertStringContainsString('BKA 8821', $failResult['message']);

        // 2. Supplying company fleet plate matches and links company vehicle
        $fleetResult = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'amount' => 95.00,
            'merchant_name' => 'Petronas Senai',
            'description' => 'Fleet refuel',
            'vehicle_plate' => 'BKA 8821',
        ]);

        $this->assertEquals('success', $fleetResult['status']);
        $this->assertEquals('BKA 8821', $fleetResult['vehicle_plate']);
        $this->assertEquals($companyVehicle->vehicle_id, $fleetResult['vehicle_id']);

        // 3. User explicitly supplying personal plate links personal vehicle
        $personalResult = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'amount' => 70.00,
            'merchant_name' => 'Shell Skudai',
            'description' => 'Personal car petrol for client site visit',
            'vehicle_plate' => 'JKE 1234',
        ]);

        $this->assertEquals('success', $personalResult['status']);
        $this->assertEquals('JKE 1234', $personalResult['vehicle_plate']);
        $this->assertEquals($personalVehicle->vehicle_id, $personalResult['vehicle_id']);
    }

    public function test_chatbot_system_instruction_includes_personal_and_company_fleet_registries(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        Vehicle::create([
            'user_id' => $user->user_id,
            'plate_number' => 'JKE 1234',
            'brand_model' => 'Perodua Myvi',
            'vehicle_type' => 'Car',
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        Vehicle::create([
            'plate_number' => 'WA 8888 A',
            'brand_model' => 'Toyota Hilux Fleet',
            'vehicle_type' => 'Car',
            'ownership_type' => 'company',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $service = app(GeminiChatbotService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildSystemInstruction');
        $method->setAccessible(true);
        $instruction = $method->invoke($service, $user);

        $this->assertStringContainsString('Staff Personal Vehicles', $instruction);
        $this->assertStringContainsString('JKE 1234', $instruction);
        $this->assertStringContainsString('Company Fleet Vehicles', $instruction);
        $this->assertStringContainsString('WA 8888 A', $instruction);
        $this->assertStringContainsString('Tuntutan Resit Perbelanjaan', $instruction);
        $this->assertStringContainsString('Tuntutan Elaun Perjalanan (Mileage)', $instruction);
    }

    public function test_chatbot_system_instruction_injects_live_server_temporal_operating_context(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $service = app(GeminiChatbotService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildSystemInstruction');
        $method->setAccessible(true);
        $instruction = $method->invoke($service, $user);

        $now = now()->timezone('Asia/Kuala_Lumpur');
        $currentDateMalay = $now->locale('ms')->translatedFormat('l, d F Y');
        $currentDateEn = $now->format('l, F j, Y');

        $this->assertStringContainsString('Temporal Operating Context (LIVE SERVER CLOCK):', $instruction);
        $this->assertStringContainsString('(Asia/Kuala_Lumpur)', $instruction);
        $this->assertStringContainsString($currentDateMalay, $instruction);
        $this->assertStringContainsString($currentDateEn, $instruction);
        $this->assertStringContainsString('Zone 1 (Core In-Scope)', $instruction);
        $this->assertStringContainsString('Zone 2 (Office Small Talk & Temporal Context)', $instruction);
        $this->assertStringContainsString('Zone 3 (Out-of-Scope Rejection)', $instruction);
    }

    public function test_out_of_scope_query_triggers_early_backend_interception_without_gemini_call(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $user = User::factory()->create(['role' => 'Staff']);
        $this->actingAs($user);

        $outOfScopeQueries = [
            'resepi rendang tok sedap',
            'write python code to solve leetcode two sum',
            'siapa presiden amerika sekarang?',
            'tulis puisi cinta untuk saya',
            'harga crypto bitcoin hari ini berapa',
        ];

        foreach ($outOfScopeQueries as $query) {
            $response = $this->postJson(route('chatbot.message'), [
                'message' => $query,
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'status' => 'success',
            ]);

            $reply = $response->json('reply');
            $this->assertStringContainsString('Maaf, saya ialah SmartClaim Assistant', $reply);
            $this->assertStringContainsString('⚡ Tindakan: Semak status tuntutan saya', $reply);
            $this->assertStringContainsString('⚡ Tindakan: Saya mahu hantar tuntutan resit perbelanjaan', $reply);
            $this->assertStringContainsString('⚡ Tindakan: Saya mahu buat tuntutan elaun perjalanan (mileage)', $reply);
        }

        // Verify zero outgoing HTTP calls were made to Gemini API
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_explicitly_out_of_scope_helper_method_logic(): void
    {
        $service = app(GeminiChatbotService::class);

        // Explicitly out of scope
        $this->assertTrue($service->isExplicitlyOutOfScope('cara masak rendang'));
        $this->assertTrue($service->isExplicitlyOutOfScope('write python code to reverse a string'));
        $this->assertTrue($service->isExplicitlyOutOfScope('lirik lagu negaraku'));

        // Always allows image receipt attachment even if text seems off
        $this->assertFalse($service->isExplicitlyOutOfScope('cara masak rendang', true));

        // In-scope queries must never be intercepted
        $this->assertFalse($service->isExplicitlyOutOfScope('Saya nak claim petrol'));
        $this->assertFalse($service->isExplicitlyOutOfScope('Semak status tuntutan saya'));
        $this->assertFalse($service->isExplicitlyOutOfScope('Berapa baki bajet saya'));

        // Office small talk and date queries must pass to Zone 2
        $this->assertFalse($service->isExplicitlyOutOfScope('Hai selamat pagi'));
        $this->assertFalse($service->isExplicitlyOutOfScope('Esok hari apa?'));
        $this->assertFalse($service->isExplicitlyOutOfScope('Tarikh hari ini'));
        $this->assertFalse($service->isExplicitlyOutOfScope('Terima kasih'));
    }

    public function test_receipt_upload_invokes_ocr_and_populates_session_baseline(): void
    {
        config(['services.gemini.api_key' => 'test-api-key']);
        $user = User::factory()->create(['role' => 'Staff']);
        $this->actingAs($user);

        $ocrMock = $this->createMock(\App\Services\ReceiptOcrService::class);
        $ocrMock->expects($this->once())
            ->method('extractReceiptData')
            ->willReturn([
                'success' => true,
                'category' => 'Office Supplies',
                'suggested_category' => 'Office Supplies',
                'merchant' => 'Kedai Runcit Mulia',
                'amount' => 100.20,
                'date' => '2026-10-25',
                'raw_text' => "KEDAI RUNCIT MULIA\nTOTAL: RM 100.20\nTERIMA KASIH",
                'confidence' => 88.50,
            ]);
        $this->app->instance(\App\Services\ReceiptOcrService::class, $ocrMock);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "Merchant: Kedai Runcit Mulia\nDate: 2026-10-25\nAmount: RM 100.20\nCategory: Office Supplies\nPurpose: Supplies\n\n⚡ Action: Confirm and submit"],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('test_receipt.jpg');

        $response = $this->post(route('chatbot.message'), [
            'receipt' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertTrue(session()->has('chatbot_ocr_baseline'));
        $baseline = session('chatbot_ocr_baseline');
        $this->assertEquals('Office Supplies', $baseline['predicted_category']);
        $this->assertStringContainsString('KEDAI RUNCIT MULIA', $baseline['raw_text']);
        $this->assertEquals(100.20, $baseline['ocr_amount']);
    }

    public function test_changing_category_during_chatbot_submission_logs_ai_feedback_discrepancy_record(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $this->actingAs($user);

        session([
            'chatbot_ocr_baseline' => [
                'predicted_category' => 'Meals & Entertainment',
                'raw_text' => 'Sample OCR raw text receipt content',
                'ocr_merchant' => 'KFC Senai',
                'ocr_amount' => 45.00,
                'ocr_date' => '2026-10-25',
                'confidence' => 88.50,
            ],
        ]);

        $actionService = app(ChatbotActionService::class);
        $result = $actionService->executeAction('submit_claim', [
            'category' => 'Office Supplies & Stationery',
            'amount' => 45.00,
            'merchant_name' => 'KFC Senai',
            'description' => 'Team stationery expenses',
        ]);

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('ai_feedback', [
            'field_name' => 'category',
            'predicted_value' => 'Meals & Entertainment',
            'actual_value' => 'Office Supplies & Stationery',
            'correction_status' => 'corrected_by_staff',
        ]);

        // Session baseline must be cleared after recording
        $this->assertNull(session('chatbot_ocr_baseline'));
    }

    public function test_bilingual_response_mirroring_for_both_english_and_malay_queries(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $service = app(GeminiChatbotService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildSystemInstruction');
        $method->setAccessible(true);

        // 1. English query must inject English Active Language Directive
        $instructionEn = $method->invoke($service, $user, 'What is the status of my recent claims?');
        $this->assertStringContainsString('Current User Language: English (Default)', $instructionEn);
        $this->assertStringContainsString('⚡ Action:', $instructionEn);

        // 2. Malay query must inject Malay Active Language Directive
        $instructionMs = $method->invoke($service, $user, '⚡ Tindakan: Semak status tuntutan saya');
        $this->assertStringContainsString('Current User Language: Bahasa Melayu', $instructionMs);
        $this->assertStringContainsString('⚡ Tindakan:', $instructionMs);
    }

    public function test_fuel_claims_without_plate_cannot_execute_submission(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $this->actingAs($user);

        \App\Models\Vehicle::create([
            'plate_number' => 'FLEET-999',
            'brand_model' => 'Toyota Hilux Fleet',
            'vehicle_type' => 'Car',
            'ownership_type' => 'company',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $actionService = app(ChatbotActionService::class);
        $result = $actionService->executeAction('submit_claim', [
            'category' => 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'amount' => 85.00,
            'merchant_name' => 'Petronas Senai',
            'description' => 'Fleet refuel',
        ]);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Vehicle Compliance Required', $result['message']);
        $this->assertStringContainsString('FLEET-999', $result['message']);
    }
}
