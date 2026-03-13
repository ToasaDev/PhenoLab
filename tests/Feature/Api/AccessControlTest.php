<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Observation;
use App\Models\PhenologicalStage;
use App\Models\Plant;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $guest; // not used as actingAs — just omitted
    private User $user;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->staff = User::factory()->staff()->create();
    }

    // ── Guest access ─────────────────────────────────────────────

    public function test_guest_can_access_ods_observations_page(): void
    {
        $response = $this->get('/observations-ods');
        $response->assertOk();
    }

    public function test_guest_can_list_sites(): void
    {
        $response = $this->getJson('/api/v1/sites');
        $response->assertOk();
    }

    public function test_guest_can_list_observations(): void
    {
        $response = $this->getJson('/api/v1/observations');
        $response->assertOk();
    }

    public function test_guest_cannot_create_site(): void
    {
        $response = $this->postJson('/api/v1/sites', [
            'name' => 'Test', 'latitude' => 45.0, 'longitude' => 4.0, 'environment' => 'garden',
        ]);
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_create_observation(): void
    {
        $response = $this->postJson('/api/v1/observations', [
            'observation_date' => '2025-06-01',
            'plant_id' => 1,
            'phenological_stage_id' => 1,
        ]);
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_statistics(): void
    {
        $response = $this->getJson('/api/v1/statistics');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_comparison(): void
    {
        $response = $this->getJson('/api/v1/comparison');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_activity_log(): void
    {
        $response = $this->getJson('/api/v1/activity');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_create_category(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test', 'category_type' => 'trees',
        ]);
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');
        $response->assertUnauthorized();
    }

    // ── Authenticated non-staff user ─────────────────────────────

    public function test_user_can_create_site(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/sites', [
            'name' => 'Mon jardin',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'environment' => 'garden',
        ]);
        $response->assertCreated();
    }

    public function test_user_can_create_observation(): void
    {
        $site = Site::factory()->create(['owner_id' => $this->user->id]);
        $plant = Plant::factory()->create(['site_id' => $site->id, 'owner_id' => $this->user->id]);
        $stage = PhenologicalStage::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/observations', [
            'observation_date' => '2025-06-01',
            'plant_id' => $plant->id,
            'phenological_stage_id' => $stage->id,
        ]);
        $response->assertCreated();
    }

    public function test_user_can_access_statistics(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/statistics');
        $response->assertOk();
    }

    public function test_user_can_access_activity_log(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/activity');
        $response->assertOk();
    }

    public function test_user_cannot_create_category(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/categories', [
            'name' => 'Test Category', 'category_type' => 'trees',
        ]);
        $response->assertForbidden();
    }

    public function test_user_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user)->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated',
        ]);
        $response->assertForbidden();
    }

    public function test_user_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/categories/{$category->id}");
        $response->assertForbidden();
    }

    public function test_user_cannot_create_phenological_stage(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/phenological-stages', [
            'stage_code' => '99',
            'stage_description' => 'Test',
            'main_event_code' => 1,
            'main_event_description' => 'Test event',
        ]);
        $response->assertForbidden();
    }

    public function test_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/admin/dashboard');
        $response->assertForbidden();
    }

    public function test_user_cannot_import_ods(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/admin/import-ods');
        $response->assertForbidden();
    }

    public function test_user_cannot_validate_observation(): void
    {
        $site = Site::factory()->create();
        $plant = Plant::factory()->create(['site_id' => $site->id]);
        $stage = PhenologicalStage::factory()->create();
        $observation = Observation::factory()->create([
            'plant_id' => $plant->id,
            'phenological_stage_id' => $stage->id,
            'observer_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/observations/{$observation->id}/validate");
        $response->assertForbidden();
    }

    public function test_user_cannot_bulk_validate_observations(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/observations/bulk-validate', [
            'observation_ids' => [1],
        ]);
        $response->assertForbidden();
    }

    public function test_user_cannot_sync_taxons_gbif(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/taxons/sync-gbif');
        $response->assertForbidden();
    }

    // ── Staff user ───────────────────────────────────────────────

    public function test_staff_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/api/v1/admin/dashboard');
        $response->assertOk();
    }

    public function test_staff_can_create_category(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/v1/categories', [
            'name' => 'Staff Category',
            'category_type' => 'trees',
        ]);
        $response->assertCreated();
    }

    public function test_staff_can_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->staff)->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated by staff',
        ]);
        $response->assertOk();
    }

    public function test_staff_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->staff)->deleteJson("/api/v1/categories/{$category->id}");
        $response->assertNoContent();
    }

    public function test_staff_can_create_phenological_stage(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/v1/phenological-stages', [
            'stage_code' => '99',
            'stage_description' => 'Test stage',
            'main_event_code' => 1,
            'main_event_description' => 'Test event',
        ]);
        $response->assertCreated();
    }

    public function test_staff_can_validate_observation(): void
    {
        $site = Site::factory()->create();
        $plant = Plant::factory()->create(['site_id' => $site->id]);
        $stage = PhenologicalStage::factory()->create();
        $observation = Observation::factory()->create([
            'plant_id' => $plant->id,
            'phenological_stage_id' => $stage->id,
            'observer_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->staff)->postJson("/api/v1/observations/{$observation->id}/validate");
        $response->assertOk();
    }

    public function test_staff_can_access_statistics(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/api/v1/statistics');
        $response->assertOk();
    }

    // ── Owner-based access ───────────────────────────────────────

    public function test_user_cannot_update_other_users_site(): void
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/sites/{$site->id}", [
            'name' => 'Hijacked',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
        ]);
        $response->assertForbidden();
    }

    public function test_staff_can_update_any_site(): void
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->actingAs($this->staff)->putJson("/api/v1/sites/{$site->id}", [
            'name' => 'Updated by staff',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
        ]);
        $response->assertOk();
    }

    public function test_user_cannot_delete_other_users_observation(): void
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create();
        $plant = Plant::factory()->create(['site_id' => $site->id]);
        $stage = PhenologicalStage::factory()->create();
        $observation = Observation::factory()->create([
            'plant_id' => $plant->id,
            'phenological_stage_id' => $stage->id,
            'observer_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/observations/{$observation->id}");
        $response->assertForbidden();
    }

    // ── Filament panel access ────────────────────────────────────

    public function test_user_model_staff_can_access_panel(): void
    {
        $this->assertTrue($this->staff->canAccessPanel(new \Filament\Panel()));
    }

    public function test_user_model_regular_cannot_access_panel(): void
    {
        $this->assertFalse($this->user->canAccessPanel(new \Filament\Panel()));
    }

    public function test_superuser_can_access_panel(): void
    {
        $superuser = User::factory()->superuser()->create();
        $this->assertTrue($superuser->canAccessPanel(new \Filament\Panel()));
    }
}
