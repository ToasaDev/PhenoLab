<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('altitude')->nullable();
            $table->string('environment', 20)->nullable()->comment('urban, suburban, rural, forest, garden, natural, agricultural');
            $table->boolean('is_private')->default(false);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('soil_type', 100)->nullable();
            $table->string('exposure', 20)->nullable()->comment('north, northeast, east, southeast, south, southwest, west, northwest');
            $table->string('slope', 20)->nullable()->comment('flat, gentle, moderate, steep');
            $table->string('climate_zone', 50)->nullable();
            $table->string('site_plan_image', 255)->nullable();
            $table->float('plan_width_meters')->nullable();
            $table->float('plan_height_meters')->nullable();
            $table->json('drawing_overlay')->nullable();
            $table->timestamps();

            $table->index('owner_id');
            $table->index('environment');
            $table->index('is_private');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
