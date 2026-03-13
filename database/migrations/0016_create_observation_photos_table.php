<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observation_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->string('image', 255);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_type', 20)->default('phenological_state')->comment('phenological_state, detail, comparison, context, measurement');
            $table->foreignId('photographer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['observation_id', 'photo_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observation_photos');
    }
};
