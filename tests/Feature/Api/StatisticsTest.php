<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_statistics(): void
    {
        $response = $this->getJson('/api/v1/statistics');
        $response->assertUnauthorized();
    }

    public function test_authenticated_gets_global_and_user_stats(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/statistics');
        $response->assertOk()
            ->assertJsonStructure(['global', 'user']);
    }
}
