<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxon_id')->constrained('taxons')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('language', 10)->comment('fr, it, en, de, es, pt, ca, oc, regional, other');
            $table->string('region', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['taxon_id', 'name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_names');
    }
};
