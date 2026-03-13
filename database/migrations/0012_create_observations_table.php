<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->date('observation_date');
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->foreignId('phenological_stage_id')->constrained('phenological_stages')->cascadeOnDelete();
            $table->foreignId('observer_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('intensity')->nullable()->comment('1-5');
            $table->float('temperature')->nullable();
            $table->string('weather_condition', 20)->nullable();
            $table->unsignedTinyInteger('humidity')->nullable();
            $table->float('wind_speed')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('confidence_level')->default(3);
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validation_date')->nullable();
            $table->time('time_of_day')->nullable();
            $table->unsignedSmallInteger('day_of_year')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('observation_date');
            $table->index(['plant_id', 'observation_date']);
            $table->index('phenological_stage_id');
            $table->index('observer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
