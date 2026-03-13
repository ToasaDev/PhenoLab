<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_global_statistics(): void
    {
        $response = $this->getJson('/api/v1/statistics');
        $response->assertOk()
            ->assertJsonStructure(['total_sites', 'total_plants', 'total_observations']);
    }

    public function test_authenticated_gets_user_stats(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/statistics');
        $response->assertOk()
            ->assertJsonStructure(['total_sites', 'total_plants', 'total_observations', 'user']);
    }
}
