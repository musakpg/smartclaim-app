<?php

namespace Tests\Feature;

use App\Models\User;
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
}
