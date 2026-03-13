<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->string('image', 255);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_type', 20)->default('general')->comment('general, leaves, flowers, fruits, bark, habitat, detail');
            $table->foreignId('photographer_id')->constrained('users')->cascadeOnDelete();
            $table->date('taken_date')->nullable();
            $table->string('camera_model', 100)->nullable();
            $table->string('focal_length', 20)->nullable();
            $table->string('aperture', 20)->nullable();
            $table->string('iso', 20)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->boolean('is_main_photo')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['plant_id', 'photo_type']);
            $table->index('is_main_photo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_photos');
    }
};
