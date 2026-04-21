<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultivars', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name', 150);
            $table->string('full_name', 255)->nullable();
            $table->foreignId('taxon_id')->nullable()->constrained('taxons')->nullOnDelete();
            $table->string('upov_code', 30)->nullable();
            $table->string('wikidata_id', 20)->nullable();

            // Classification
            $table->string('type', 30)->default('cultivar'); // cultivar, variety, clone, rootstock
            $table->string('synonyms', 500)->nullable();

            // Origin & history
            $table->string('origin_country', 100)->nullable();
            $table->string('origin_region', 150)->nullable();
            $table->string('breeder', 200)->nullable();
            $table->string('year_introduced', 20)->nullable();
            $table->string('parentage', 300)->nullable();

            // Fruit characteristics
            $table->string('fruit_color', 100)->nullable();
            $table->string('fruit_size', 50)->nullable();
            $table->string('fruit_shape', 100)->nullable();
            $table->string('flesh_color', 100)->nullable();
            $table->string('flesh_texture', 100)->nullable();
            $table->string('flavor_profile', 200)->nullable();
            $table->string('skin_type', 100)->nullable();

            // Harvest & phenology
            $table->string('harvest_period', 100)->nullable();
            $table->string('flowering_period', 100)->nullable();
            $table->string('maturity_group', 50)->nullable(); // early, mid-season, late
            $table->string('storage_life', 100)->nullable();

            // Agronomic traits
            $table->string('vigor', 50)->nullable();
            $table->string('productivity', 50)->nullable();
            $table->boolean('self_fertile')->nullable();
            $table->string('pollinators', 300)->nullable();
            $table->string('rootstocks', 300)->nullable();
            $table->string('disease_resistance', 300)->nullable();
            $table->string('cold_hardiness', 100)->nullable();

            // Usage
            $table->string('usage_types', 200)->nullable();

            // Media & references
            $table->string('image_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 500)->nullable();

            // Registration data (from EUPVP)
            $table->string('registration_country', 10)->nullable();
            $table->string('registration_status', 30)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('national_id', 100)->nullable();
            $table->string('eupvp_uuid', 100)->nullable();

            $table->json('extra')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('taxon_id');
            $table->index('upov_code');
            $table->index('type');
            $table->index('wikidata_id');
            $table->index('registration_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultivars');
    }
};
