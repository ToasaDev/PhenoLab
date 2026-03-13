<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Plant;
use App\Models\PlantPhoto;
use App\Models\Site;
use App\Models\Taxon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Plant $plant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
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
    }

    public function test_can_upload_plant_photo(): void
    {
        $file = UploadedFile::fake()->image('plant.jpg', 800, 600);

        $response = $this->actingAs($this->user)->postJson('/api/v1/plant-photos', [
            'plant_id' => $this->plant->id,
            'image' => $file,
            'photo_type' => 'general',
            'title' => 'Photo de test',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('plant_photos', [
            'plant_id' => $this->plant->id,
            'title' => 'Photo de test',
        ]);
    }

    public function test_first_photo_becomes_main(): void
    {
        $file = UploadedFile::fake()->image('plant.jpg');

        $this->actingAs($this->user)->postJson('/api/v1/plant-photos', [
            'plant_id' => $this->plant->id,
            'image' => $file,
            'photo_type' => 'general',
        ]);

        $photo = PlantPhoto::first();
        $this->assertTrue($photo->is_main_photo);
    }

    public function test_can_delete_photo(): void
    {
        $photo = PlantPhoto::factory()->create([
            'plant_id' => $this->plant->id,
            'photographer_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/plant-photos/{$photo->id}");
        $response->assertNoContent();
    }

    public function test_unauthenticated_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('plant.jpg');

        $response = $this->postJson('/api/v1/plant-photos', [
            'plant_id' => $this->plant->id,
            'image' => $file,
        ]);

        $response->assertUnauthorized();
    }
}
