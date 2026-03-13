<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxons', function (Blueprint $table) {
            $table->id();
            $table->string('taxon_id', 20)->unique();
            $table->string('kingdom', 100)->default('Plantae');
            $table->string('phylum', 100)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('order', 100)->nullable();
            $table->string('family', 100)->nullable();
            $table->string('genus', 100);
            $table->string('species', 100);
            $table->string('binomial_name', 255);
            $table->string('subspecies', 100)->nullable();
            $table->string('variety', 100)->nullable();
            $table->string('cultivar', 100)->nullable();
            $table->string('common_name_fr', 1000)->nullable();
            $table->string('common_name_it', 1000)->nullable();
            $table->string('common_name_en', 1000)->nullable();
            $table->string('author', 1000)->nullable();
            $table->unsignedInteger('publication_year')->nullable();
            $table->unsignedBigInteger('gbif_id')->unique()->nullable();
            $table->string('gbif_status', 50)->nullable();
            $table->string('gbif_rank', 50)->nullable();
            $table->string('gbif_canonical_name', 1000)->nullable();
            $table->timestamp('gbif_synced_at')->nullable();
            $table->timestamps();

            $table->index(['genus', 'species']);
            $table->index('family');
            $table->index('binomial_name');
            $table->index('gbif_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxons');
    }
};
