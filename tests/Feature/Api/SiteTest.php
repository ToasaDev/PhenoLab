<?php

namespace Tests\Feature\Api;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_public_sites(): void
    {
        Site::factory()->count(3)->create(['is_private' => false]);

        $response = $this->getJson('/api/v1/sites');
        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_site_when_authenticated(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/sites', [
            'name' => 'Mon jardin',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'environment' => 'garden',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Mon jardin');
    }

    public function test_cannot_create_site_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/sites', [
            'name' => 'Test',
            'latitude' => 45.0,
            'longitude' => 4.0,
        ]);

        $response->assertUnauthorized();
    }

    public function test_can_view_site_detail(): void
    {
        $site = Site::factory()->create(['is_private' => false]);

        $response = $this->getJson("/api/v1/sites/{$site->id}");
        $response->assertOk()
            ->assertJsonPath('data.id', $site->id);
    }

    public function test_can_get_geojson(): void
    {
        Site::factory()->count(2)->create(['is_private' => false]);

        $response = $this->getJson('/api/v1/sites/geojson');
        $response->assertOk()
            ->assertJsonPath('type', 'FeatureCollection');
    }

    public function test_nearby_sites(): void
    {
        Site::factory()->create([
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'is_private' => false,
        ]);

        $response = $this->getJson('/api/v1/sites/nearby?lat=45.76&lon=4.83&radius_km=10');
        $response->assertOk();
    }

    public function test_owner_can_update_site(): void
    {
        $site = Site::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/sites/{$site->id}", [
            'name' => 'Updated name',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated name');
    }
}
