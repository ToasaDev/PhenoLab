<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Plant;
use App\Models\Site;
use App\Models\SitePlanLayer;
use App\Models\Taxon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $staffUser;
    private Site $site;
    private Taxon $taxon;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->staffUser = User::factory()->create(['is_staff' => true]);
        $this->site = Site::factory()->create(['owner_id' => $this->user->id]);
        $this->taxon = Taxon::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function createPlant(array $attrs = []): Plant
    {
        return Plant::factory()->create(array_merge([
            'owner_id' => $this->user->id,
            'site_id' => $this->site->id,
            'taxon_id' => $this->taxon->id,
            'category_id' => $this->category->id,
        ], $attrs));
    }

    // ── Layers ───────────────────────────────────────────────────────

    public function test_can_list_layers(): void
    {
        SitePlanLayer::factory()->count(2)->create(['site_id' => $this->site->id]);

        $this->getJson("/api/v1/sites/{$this->site->id}/layers")
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_can_create_layer(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/layers", [
                'name' => 'Printemps 2025',
                'start_date' => '2025-03-01',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Printemps 2025');
    }

    public function test_can_update_layer(): void
    {
        $layer = SitePlanLayer::factory()->create(['site_id' => $this->site->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/v1/sites/{$this->site->id}/layers/{$layer->id}", [
                'name' => 'Renamed',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');
    }

    public function test_can_delete_layer(): void
    {
        $layer = SitePlanLayer::factory()->create(['site_id' => $this->site->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/sites/{$this->site->id}/layers/{$layer->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('site_plan_layers', ['id' => $layer->id]);
    }

    public function test_can_save_drawing_overlay_to_layer(): void
    {
        $layer = SitePlanLayer::factory()->create(['site_id' => $this->site->id]);
        $shapes = [
            ['type' => 'rect', 'x' => 10, 'y' => 20, 'width' => 100, 'height' => 50],
        ];

        $this->actingAs($this->user)
            ->patchJson("/api/v1/sites/{$this->site->id}/layers/{$layer->id}", [
                'drawing_overlay' => $shapes,
            ])
            ->assertOk();

        $layer->refresh();
        $this->assertCount(1, $layer->drawing_overlay);
        $this->assertEquals('rect', $layer->drawing_overlay[0]['type']);
    }

    // ── Bulk Map Positions ───────────────────────────────────────────

    public function test_bulk_update_map_positions(): void
    {
        $p1 = $this->createPlant();
        $p2 = $this->createPlant();
        $layer = SitePlanLayer::factory()->create(['site_id' => $this->site->id]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/plants/bulk-update-map-positions', [
                'site_id' => $this->site->id,
                'layer_id' => $layer->id,
                'positions' => [
                    ['plant_id' => $p1->id, 'map_position_x' => 25.5, 'map_position_y' => 40.2],
                    ['plant_id' => $p2->id, 'map_position_x' => 75.0, 'map_position_y' => 60.0],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('updated_count', 2);

        $p1->refresh();
        $p2->refresh();
        $this->assertEquals(25.5, $p1->map_position_x);
        $this->assertEquals(40.2, $p1->map_position_y);
        $this->assertEquals($layer->id, $p1->layer_id);
        $this->assertEquals(75.0, $p2->map_position_x);
    }

    public function test_bulk_update_validates_range(): void
    {
        $plant = $this->createPlant();

        $this->actingAs($this->user)
            ->postJson('/api/v1/plants/bulk-update-map-positions', [
                'positions' => [
                    ['plant_id' => $plant->id, 'map_position_x' => 150, 'map_position_y' => 50],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_bulk_update_requires_auth(): void
    {
        $plant = $this->createPlant();

        $this->postJson('/api/v1/plants/bulk-update-map-positions', [
            'positions' => [
                ['plant_id' => $plant->id, 'map_position_x' => 50, 'map_position_y' => 50],
            ],
        ])->assertUnauthorized();
    }

    // ── Plant Index Layer Filter ─────────────────────────────────────

    public function test_can_filter_plants_by_layer(): void
    {
        $layer = SitePlanLayer::factory()->create(['site_id' => $this->site->id]);
        $this->createPlant(['layer_id' => $layer->id]);
        $this->createPlant(['layer_id' => null]);

        $response = $this->getJson("/api/v1/plants?site={$this->site->id}&layer={$layer->id}");
        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_page_size_alias_works(): void
    {
        $this->createPlant();
        $this->createPlant();

        $response = $this->getJson('/api/v1/plants?page_size=1');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ── Site Map ─────────────────────────────────────────────────────

    public function test_site_map_includes_plants_with_map_position(): void
    {
        $this->createPlant([
            'map_position_x' => 30.0,
            'map_position_y' => 50.0,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson("/api/v1/plants/site-map?site_id={$this->site->id}");
        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_site_map_includes_plants_with_gps(): void
    {
        $this->createPlant([
            'latitude' => 45.0,
            'longitude' => 4.0,
            'map_position_x' => null,
        ]);

        $response = $this->getJson("/api/v1/plants/site-map?site_id={$this->site->id}");
        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_site_map_excludes_plants_without_any_position(): void
    {
        $this->createPlant([
            'latitude' => null,
            'longitude' => null,
            'map_position_x' => null,
            'map_position_y' => null,
        ]);

        $response = $this->getJson("/api/v1/plants/site-map?site_id={$this->site->id}");
        $response->assertOk();
        $this->assertCount(0, $response->json());
    }
}
