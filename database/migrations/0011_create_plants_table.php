<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('taxon_id')->constrained('taxons')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('plant_positions')->nullOnDelete();
            $table->date('planting_date')->nullable();
            $table->unsignedInteger('age_years')->nullable();
            $table->string('height_category', 20)->nullable()->comment('seedling, young, medium, mature, large');
            $table->float('exact_height')->nullable();
            $table->string('health_status', 20)->default('good')->comment('excellent, good, fair, poor, dead');
            $table->string('status', 20)->default('alive')->comment('alive, dead, replaced, removed');
            $table->date('death_date')->nullable();
            $table->string('death_cause', 20)->nullable()->comment('disease, pests, frost, drought, flooding, wind, age, accident, human, unknown, other');
            $table->text('death_notes')->nullable();
            $table->foreignId('replaces_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->string('clone_or_accession', 100)->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_private')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->float('gps_accuracy')->nullable();
            $table->timestamp('gps_recorded_at')->nullable();
            $table->float('site_position_x')->nullable();
            $table->float('site_position_y')->nullable();
            $table->float('map_position_x')->nullable();
            $table->float('map_position_y')->nullable();
            $table->foreignId('layer_id')->nullable()->constrained('site_plan_layers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('anecdotes')->nullable();
            $table->text('cultural_significance')->nullable();
            $table->text('ecological_notes')->nullable();
            $table->text('care_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('owner_id');
            $table->index('site_id');
            $table->index('taxon_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
