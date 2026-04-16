<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_cultivation_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();

            // ── WHEN TO PLANT ────────────────────────────────────────
            $table->json('planting_months')->nullable()->comment('Array of int 1-12');
            $table->json('sowing_months')->nullable()->comment('Array of int 1-12');
            $table->json('harvest_months')->nullable()->comment('Array of int 1-12');
            $table->json('flowering_months')->nullable()->comment('Array of int 1-12');

            // ── WHERE TO GROW ────────────────────────────────────────
            $table->string('exposure', 30)->nullable()->comment('full_sun|partial_shade|shade|full_shade');
            $table->string('hardiness_min', 20)->nullable()->comment('Min temperature (e.g. -15°C)');
            $table->string('usda_zone', 20)->nullable()->comment('USDA hardiness zone (e.g. 7-9)');
            $table->json('suitable_environments')->nullable()->comment('Array of environment types');
            $table->json('soil_types')->nullable()->comment('Array: clay|sandy|loam|chalky|peaty|silty');
            $table->string('soil_ph', 30)->nullable()->comment('e.g. 6.0-7.5 or acid|neutral|alkaline');
            $table->string('soil_drainage', 30)->nullable()->comment('well_drained|moist|wet|dry');
            $table->string('soil_fertility', 30)->nullable()->comment('poor|average|rich');
            $table->decimal('mature_height_min', 8, 2)->nullable()->comment('Meters');
            $table->decimal('mature_height_max', 8, 2)->nullable()->comment('Meters');
            $table->decimal('mature_spread_min', 8, 2)->nullable()->comment('Meters');
            $table->decimal('mature_spread_max', 8, 2)->nullable()->comment('Meters');

            // ── CARE ──────────────────────────────────────────────────
            $table->string('watering_needs', 30)->nullable()->comment('low|moderate|regular|high');
            $table->text('watering_notes')->nullable();
            $table->string('fertilizing_frequency', 50)->nullable();
            $table->text('fertilizing_notes')->nullable();
            $table->string('pruning_period', 100)->nullable();
            $table->text('pruning_notes')->nullable();
            $table->string('mulching', 50)->nullable();
            $table->string('winter_protection', 100)->nullable();
            $table->text('pest_susceptibility')->nullable();
            $table->text('disease_susceptibility')->nullable();
            $table->json('companion_plants')->nullable()->comment('Free-text list');
            $table->json('avoid_near')->nullable()->comment('Free-text list');
            $table->string('propagation_methods', 200)->nullable();
            $table->string('cultivation_difficulty', 30)->nullable()->comment('easy|medium|hard|expert');
            $table->json('usage_types')->nullable()->comment('ornamental|edible|medicinal|hedging|shade|fragrance|wildlife|...');
            $table->boolean('is_edible')->default(false);
            $table->boolean('is_toxic')->default(false);

            // ── META ──────────────────────────────────────────────────
            $table->text('notes')->nullable()->comment('Free notes');
            $table->string('source', 255)->nullable()->comment('Reference / book / URL');
            $table->json('extra')->nullable()->comment('Escape-hatch for non-modeled fields');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('plant_id');
            $table->index('exposure');
            $table->index('cultivation_difficulty');
            $table->index('watering_needs');
            $table->index('usda_zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_cultivation_profiles');
    }
};
