<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->float('gps_accuracy')->nullable();
            $table->timestamp('gps_recorded_at')->nullable();
            $table->float('site_position_x')->nullable();
            $table->float('site_position_y')->nullable();
            $table->text('soil_notes')->nullable();
            $table->text('exposure_notes')->nullable();
            $table->text('microclimate_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['site_id', 'label']);
            $table->index(['site_id', 'is_active']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_positions');
    }
};
