<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Plant;
use App\Models\Site;
use App\Models\Taxon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private User $staffUser;
    private Site $site;
    private Taxon $taxon;
    private Taxon $taxon2;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->staffUser = User::factory()->create(['is_staff' => true]);
        $this->site = Site::factory()->create(['owner_id' => $this->user->id]);
        $this->taxon = Taxon::factory()->create();
        $this->taxon2 = Taxon::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function createPlant(array $attrs = []): Plant
    {
        return Plant::factory()->create(array_merge([
            'owner_id' => $this->user->id,
            'site_id' => $this->site->id,
            'taxon_id' => $this->taxon->id,
            'category_id' => $this->category->id,
            'status' => 'alive',
        ], $attrs));
    }

    // ── Basic CRUD ────────────────────────────────────────

    public function test_can_list_plants(): void
    {
        Plant::factory()->count(3)->create([
            'owner_id' => $this->user->id,
            'site_id' => $this->site->id,
            'taxon_id' => $this->taxon->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/v1/plants');
        $response->assertOk();
    }

    public function test_can_create_plant(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/plants', [
            'name' => 'Chene centenaire',
            'taxon_id' => $this->taxon->id,
            'category_id' => $this->category->id,
            'site_id' => $this->site->id,
            'health_status' => 'good',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('plants', ['name' => 'Chene centenaire']);
    }

    public function test_cannot_delete_other_users_plant(): void
    {
        $plant = $this->createPlant(['owner_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/plants/{$plant->id}");
        $response->assertForbidden();
    }

    // ── Mark Dead ─────────────────────────────────────────

    public function test_mark_dead_success(): void
    {
        $plant = $this->createPlant();

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_date' => '2025-12-01',
            'death_cause' => 'frost',
            'death_notes' => 'Gel tardif',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', "Plante \"{$plant->name}\" marquée comme morte");

        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'status' => 'dead',
            'health_status' => 'dead',
            'death_cause' => 'frost',
        ]);
    }

    public function test_mark_dead_requires_death_date(): void
    {
        $plant = $this->createPlant();

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_cause' => 'frost',
        ]);

        $response->assertUnprocessable();
    }

    public function test_mark_dead_refuses_already_dead(): void
    {
        $plant = $this->createPlant(['status' => 'dead']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_date' => '2025-12-01',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Cette plante est déjà morte');
    }

    public function test_mark_dead_refuses_already_replaced(): void
    {
        $plant = $this->createPlant(['status' => 'replaced']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_date' => '2025-12-01',
        ]);

        $response->assertStatus(400);
    }

    public function test_mark_dead_forbidden_for_non_owner(): void
    {
        $plant = $this->createPlant();

        $response = $this->actingAs($this->otherUser)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_date' => '2025-12-01',
        ]);

        $response->assertForbidden();
    }

    public function test_mark_dead_allowed_for_staff(): void
    {
        $plant = $this->createPlant();

        $response = $this->actingAs($this->staffUser)->postJson("/api/v1/plants/{$plant->id}/mark-dead", [
            'death_date' => '2025-12-01',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('plants', ['id' => $plant->id, 'status' => 'dead']);
    }

    // ── Replace ───────────────────────────────────────────

    public function test_replace_dead_plant_success(): void
    {
        $oldPlant = $this->createPlant([
            'status' => 'dead',
            'latitude' => 45.76,
            'longitude' => 4.85,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Nouveau chene',
                'taxon' => $this->taxon2->id,
                'category' => $this->category->id,
                'planting_date' => '2026-01-15',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'old_plant', 'new_plant']);

        // Old plant marked as replaced
        $this->assertDatabaseHas('plants', [
            'id' => $oldPlant->id,
            'status' => 'replaced',
        ]);

        // New plant created with succession link
        $newPlant = Plant::where('name', 'Nouveau chene')->first();
        $this->assertNotNull($newPlant);
        $this->assertEquals($oldPlant->id, $newPlant->replaces_id);
        $this->assertEquals($oldPlant->site_id, $newPlant->site_id);
        $this->assertEquals($oldPlant->latitude, $newPlant->latitude);
        $this->assertEquals($oldPlant->longitude, $newPlant->longitude);
        $this->assertEquals('alive', $newPlant->status);
    }

    public function test_replace_alive_plant_success(): void
    {
        $oldPlant = $this->createPlant(['status' => 'alive']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Remplacement',
                'taxon' => $this->taxon->id,
                'category' => $this->category->id,
            ],
        ]);

        $response->assertCreated();

        // Old plant auto-marked as replaced with death_date = today
        $oldPlant->refresh();
        $this->assertEquals('replaced', $oldPlant->status);
        $this->assertNotNull($oldPlant->death_date);
    }

    public function test_replace_refuses_already_replaced(): void
    {
        $oldPlant = $this->createPlant(['status' => 'replaced']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Nouveau',
                'taxon' => $this->taxon->id,
                'category' => $this->category->id,
            ],
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Cette plante a déjà été remplacée');
    }

    public function test_replace_forbidden_for_non_owner(): void
    {
        $oldPlant = $this->createPlant(['status' => 'dead']);

        $response = $this->actingAs($this->otherUser)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Nouveau',
                'taxon' => $this->taxon->id,
                'category' => $this->category->id,
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_replace_allowed_for_staff(): void
    {
        $oldPlant = $this->createPlant(['status' => 'dead']);

        $response = $this->actingAs($this->staffUser)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Staff replacement',
                'taxon' => $this->taxon->id,
                'category' => $this->category->id,
            ],
        ]);

        $response->assertCreated();
    }

    public function test_replace_requires_new_plant_data(): void
    {
        $oldPlant = $this->createPlant(['status' => 'dead']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", []);

        $response->assertStatus(400);
    }

    public function test_replace_validates_new_plant_fields(): void
    {
        $oldPlant = $this->createPlant(['status' => 'dead']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => '', // empty name
                'taxon' => 99999, // non-existent
                'category' => 99999,
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_replace_is_atomic(): void
    {
        $oldPlant = $this->createPlant(['status' => 'dead']);

        // Force validation failure on new plant (invalid taxon)
        $response = $this->actingAs($this->user)->postJson("/api/v1/plants/{$oldPlant->id}/replace", [
            'new_plant' => [
                'name' => 'Test',
                'taxon' => 99999,
                'category' => $this->category->id,
            ],
        ]);

        // Old plant should NOT have been changed
        $oldPlant->refresh();
        $this->assertEquals('dead', $oldPlant->status);
    }
}
