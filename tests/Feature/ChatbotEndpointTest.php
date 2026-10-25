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
}
