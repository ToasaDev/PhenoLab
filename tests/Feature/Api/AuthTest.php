<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@phenolab.fr',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@phenolab.fr',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized();
    }

    public function test_can_check_auth_status(): void
    {
        $response = $this->getJson('/api/v1/auth/status');
        $response->assertOk()
            ->assertJson(['authenticated' => false]);
    }

    public function test_authenticated_user_gets_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/status');
        $response->assertOk()
            ->assertJson(['authenticated' => true]);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/auth/logout');
        $response->assertOk();
    }
}
