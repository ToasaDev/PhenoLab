<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ods_observations', function (Blueprint $table) {
            $table->id();
            $table->string('observation_id_ods', 20)->unique();
            $table->date('date');
            $table->string('is_missing', 10)->nullable();
            $table->text('details')->nullable();
            $table->date('creation_date')->nullable();
            $table->date('update_date')->nullable();
            $table->date('deletion_date')->nullable();
            $table->string('species_id', 20);
            $table->string('vernacular_name', 255)->nullable();
            $table->string('scientific_name', 255);
            $table->string('species_type', 100)->nullable();
            $table->string('plant_or_animal', 50);
            $table->string('individual_id', 20);
            $table->string('individual_name', 255)->nullable();
            $table->text('individual_detail')->nullable();
            $table->string('phenological_stage', 255)->nullable();
            $table->string('bbch_code', 10)->nullable();
            $table->string('station_id', 20);
            $table->string('station_name', 255);
            $table->text('station_description')->nullable();
            $table->string('station_locality', 255)->nullable();
            $table->string('habitat', 255)->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->float('altitude')->nullable();
            $table->string('insee_code', 10)->nullable();
            $table->string('department', 100)->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('scientific_name');
            $table->index('phenological_stage');
            $table->index('bbch_code');
            $table->index(['scientific_name', 'bbch_code']);
            $table->index(['latitude', 'longitude']);
            $table->index('station_id');
            $table->index('observation_id_ods');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ods_observations');
    }
};
