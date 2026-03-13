<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tela_observations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('year');
            $table->integer('day_of_year');
            $table->string('data_source', 100)->default('ODS Tela Botanica');
            $table->string('site_id_tela', 50);
            $table->string('site_name', 255);
            $table->float('site_latitude');
            $table->float('site_longitude');
            $table->float('site_altitude')->nullable();
            $table->float('site_altitude_from_ign')->nullable();
            $table->string('taxon_id_tela', 20);
            $table->string('binomial_name', 255);
            $table->string('kingdom', 100);
            $table->string('genus', 100);
            $table->string('species', 100);
            $table->string('subspecies', 100)->nullable();
            $table->string('variety', 100)->nullable();
            $table->string('taxon_clone_or_accession_code', 100)->nullable();
            $table->integer('phenological_scale_id');
            $table->string('phenological_scale', 100);
            $table->string('stage_code', 10);
            $table->string('stage_description', 255);
            $table->integer('phenological_main_event_code');
            $table->string('phenological_main_event_description', 255);
            $table->string('data_license_acronym', 50);
            $table->string('data_license', 255);
            $table->string('data_license_url', 500);
            $table->string('contact_name', 255);
            $table->string('contact_email_address', 255);
            $table->string('contact_organisation', 255)->nullable();
            $table->string('environment', 100)->nullable();
            $table->boolean('private_station')->default(false);
            $table->string('observation_number', 50);
            $table->string('observer_number', 50)->nullable();
            $table->string('aggregation', 50)->nullable();
            $table->string('drias_cell_number', 50)->nullable();
            $table->string('safran_cell_number', 50)->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('taxon_id_tela');
            $table->index('stage_code');
            $table->index(['site_latitude', 'site_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tela_observations');
    }
};
