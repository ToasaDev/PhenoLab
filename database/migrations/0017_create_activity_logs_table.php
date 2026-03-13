<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20)->comment('created, updated, deleted, replaced, marked_dead, validated, uploaded, imported, synced');
            $table->string('entity_type', 50)->comment('observation, plant, taxon, photo, site, position, system');
            $table->unsignedInteger('entity_id');
            $table->string('entity_label', 500);
            $table->boolean('is_public')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['entity_type', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['is_public', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
