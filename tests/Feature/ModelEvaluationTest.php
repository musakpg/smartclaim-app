<?php

namespace Tests\Feature;

use App\Models\AiFeedback;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ModelBenchmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_manager_can_access_model_evaluation_dashboard_successfully()
    {
        $manager = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($manager)->get(route('manager.model-evaluation'));

        $response->assertStatus(200);
        $response->assertViewIs('manager.model-evaluation');
        $response->assertSee('Model Evaluation Dashboard');
        $response->assertSee('Category Accuracy');
        $response->assertSee('Confusion Matrix');
        $response->assertSee('Continuous Active Learning Feedback Ledger');
    }

    public function test_model_evaluation_dataset_can_be_exported_as_csv_and_json()
    {
        $manager = User::factory()->create(['role' => 'Manager']);

        // Test CSV export
        $csvResponse = $this->actingAs($manager)->get(route('manager.model-evaluation.export', ['format' => 'csv']));
        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-type'), 'text/csv'));

        // Test JSON export
        $jsonResponse = $this->actingAs($manager)->get(route('manager.model-evaluation.export', ['format' => 'json']));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure(['exported_at', 'total_samples', 'dataset']);
    }

    public function test_claim_submission_discrepancy_is_recorded_in_ai_feedback()
    {
        $staff = User::firstOrCreate(
            ['email' => 'staff_eval@aeroart.com'],
            ['name' => 'Staff Eval', 'password' => bcrypt('password'), 'role' => 'Staff']
        );

        $category = Category::firstOrCreate(
            ['name' => 'Meals & Entertainment'],
            ['code' => 'meals', 'is_active' => true]
        );

        // Simulate discrepancy: OCR predicted 'Office Supplies' but user selected 'Meals & Entertainment',
        // and OCR amount predicted 50.00 but user corrected to 55.00
        $discrepancy = AiFeedback::recordDiscrepancy(
            null,
            'INV-TEST-001',
            $staff->user_id,
            'category',
            'Office Supplies',
            'Meals & Entertainment',
            85.00,
            'Sample raw OCR text from receipt'
        );

        $this->assertNotNull($discrepancy);
        $this->assertDatabaseHas('ai_feedback', [
            'field_name' => 'category',
            'predicted_value' => 'Office Supplies',
            'actual_value' => 'Meals & Entertainment',
            'correction_status' => 'corrected_by_staff',
        ]);
    }
}
