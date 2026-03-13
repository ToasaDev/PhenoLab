<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Observation;
use App\Models\PhenologicalStage;
use App\Models\Plant;
use App\Models\Site;
use App\Models\Taxon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Plant $plant;
    private PhenologicalStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $this->user->id]);
        $taxon = Taxon::factory()->create();
        $category = Category::factory()->create();
        $this->plant = Plant::factory()->create([
            'owner_id' => $this->user->id,
            'site_id' => $site->id,
            'taxon_id' => $taxon->id,
            'category_id' => $category->id,
        ]);
        $this->stage = PhenologicalStage::factory()->create();
    }

    public function test_can_create_observation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/observations', [
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
            'observation_date' => '2025-03-15',
            'confidence_level' => 4,
            'notes' => 'Début floraison',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('observations', [
            'plant_id' => $this->plant->id,
            'notes' => 'Début floraison',
        ]);
    }

    public function test_day_of_year_auto_calculated(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/observations', [
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
            'observation_date' => '2025-03-15',
        ]);

        $obs = Observation::first();
        $this->assertEquals(74, $obs->day_of_year); // March 15 = day 74
    }

    public function test_staff_can_validate_observation(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $obs = Observation::factory()->create([
            'observer_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
        ]);

        $response = $this->actingAs($staff)->postJson("/api/v1/observations/{$obs->id}/validate");
        $response->assertOk();

        $obs->refresh();
        $this->assertTrue($obs->is_validated);
        $this->assertEquals($staff->id, $obs->validated_by_id);
    }

    public function test_unauthenticated_cannot_create_observation(): void
    {
        $response = $this->postJson('/api/v1/observations', [
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
            'observation_date' => '2025-03-15',
        ]);

        $response->assertUnauthorized();
    }

    public function test_can_list_years_available(): void
    {
        Observation::factory()->create([
            'observer_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
            'observation_date' => '2024-06-15',
        ]);
        Observation::factory()->create([
            'observer_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'phenological_stage_id' => $this->stage->id,
            'observation_date' => '2025-03-15',
        ]);

        $response = $this->getJson('/api/v1/observations/years-available');
        $response->assertOk();
    }
}
